package com.tasksbase.chat.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.tasksbase.chat.BuildConfig
import kotlinx.coroutines.flow.first
import java.net.URI

private val Context.serverDataStore by preferencesDataStore(name = "server_config")

/**
 * Server address (host + port) the app talks to. The same origin serves the
 * Laravel API (`/api/v1`), broadcast auth (`/broadcasting/auth`) and the Reverb
 * WebSocket (`/app/...`) behind nginx.
 */
data class ServerConfig(val host: String, val port: Int) {

    val baseUrl: String get() = "http://$host:$port/api/v1/"

    val wsHost: String get() = host

    val wsPort: Int get() = port

    val broadcastAuthUrl: String get() = "http://$host:$port/broadcasting/auth"

    val display: String get() = "$host:$port"

    companion object {
        /** Default taken from the build config (emulator points to 10.0.2.2). */
        fun default(): ServerConfig {
            val parsed = runCatching { URI(BuildConfig.API_BASE_URL) }.getOrNull()
            val host = parsed?.host?.takeIf { it.isNotBlank() } ?: "10.0.2.2"
            val port = parsed?.port?.takeIf { it > 0 } ?: 80
            return ServerConfig(host, port)
        }
    }
}

/** Persists the server address on the device (survives app restarts). */
class ServerConfigStore(private val context: Context) {

    private companion object {
        val KEY_HOST = stringPreferencesKey("server_host")
        val KEY_PORT = intPreferencesKey("server_port")
    }

    suspend fun load(): ServerConfig {
        val prefs = context.serverDataStore.data.first()
        val fallback = ServerConfig.default()
        val host = prefs[KEY_HOST]?.takeIf { it.isNotBlank() } ?: fallback.host
        val port = prefs[KEY_PORT] ?: fallback.port
        return ServerConfig(host, port)
    }

    suspend fun save(config: ServerConfig) {
        context.serverDataStore.edit { prefs ->
            prefs[KEY_HOST] = config.host
            prefs[KEY_PORT] = config.port
        }
    }
}
