package com.tasksbase.chat.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import kotlinx.coroutines.flow.first

private val Context.sessionDataStore by preferencesDataStore(name = "chat_session")

/** Persists the auth token + current user between app launches. */
class TokenStore(private val context: Context) {

    private companion object {
        val KEY_TOKEN = stringPreferencesKey("token")
        val KEY_USER = stringPreferencesKey("user")
    }

    suspend fun save(token: String, user: UserDto?) {
        context.sessionDataStore.edit { prefs ->
            prefs[KEY_TOKEN] = token
            if (user != null) {
                prefs[KEY_USER] = ServiceLocator.gson.toJson(user)
            } else {
                prefs.remove(KEY_USER)
            }
        }
    }

    suspend fun load(): Pair<String?, UserDto?> {
        val prefs = context.sessionDataStore.data.first()
        val token = prefs[KEY_TOKEN]
        val user = prefs[KEY_USER]?.let {
            runCatching { ServiceLocator.gson.fromJson(it, UserDto::class.java) }.getOrNull()
        }
        return token to user
    }

    suspend fun clear() {
        context.sessionDataStore.edit { it.clear() }
    }
}
