package com.tasksbase.chat.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import kotlinx.coroutines.flow.first

private val Context.accountDataStore by preferencesDataStore(name = "chat_accounts")

/**
 * A single signed-in account bound to its own backend server (and its own ntfy
 * push server). Multiple accounts can coexist; one is active at a time.
 */
data class Account(
    val id: String,
    val serverHost: String,
    val serverPort: Int,
    val ntfyHost: String,
    val ntfyPort: Int,
    val ntfyTopic: String,
    val token: String,
    val user: UserDto,
) {
    fun serverConfig(): ServerConfig = ServerConfig(serverHost, serverPort)

    /** e.g. "212.109.16.56:8000" */
    val display: String get() = "$serverHost:$serverPort"

    val ntfyBaseUrl: String
        get() = if (ntfyHost.isBlank()) "" else "http://$ntfyHost:$ntfyPort"
}

/** Persists the account list and the active account id. */
class AccountStore(private val context: Context) {

    private companion object {
        val KEY_ACCOUNTS = stringPreferencesKey("accounts_json")
        val KEY_ACTIVE = stringPreferencesKey("active_account_id")
    }

    suspend fun loadAll(): Pair<List<Account>, String?> {
        val prefs = context.accountDataStore.data.first()
        val raw = prefs[KEY_ACCOUNTS]
        val list = if (raw.isNullOrBlank()) {
            emptyList()
        } else {
            runCatching {
                ServiceLocator.gson.fromJson(raw, Array<Account>::class.java).toList()
            }.getOrDefault(emptyList())
        }
        return list to prefs[KEY_ACTIVE]
    }

    suspend fun saveAll(accounts: List<Account>, activeId: String?) {
        context.accountDataStore.edit { prefs ->
            prefs[KEY_ACCOUNTS] = ServiceLocator.gson.toJson(accounts)
            if (activeId != null) prefs[KEY_ACTIVE] = activeId else prefs.remove(KEY_ACTIVE)
        }
    }
}
