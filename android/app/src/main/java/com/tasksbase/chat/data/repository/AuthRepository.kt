package com.tasksbase.chat.data.repository

import com.tasksbase.chat.data.local.Account
import com.tasksbase.chat.data.local.ServerConfig
import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.LoginRequest
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.notifications.RealtimeService

class AuthRepository(private val api: ApiService) {

    /**
     * Logs in against a specific server and stores the result as an account.
     * The same user id + server maps to the same account (re-login updates it).
     */
    suspend fun login(
        email: String,
        password: String,
        server: ServerConfig,
        ntfyHost: String,
        ntfyPort: Int,
        ntfyUser: String? = null,
        ntfyPass: String? = null,
    ): Account {
        val previousServer = ServiceLocator.server
        ServiceLocator.setLoginServer(server)

        try {
            val response = api.login(LoginRequest(email.trim(), password))

            val account = Account(
                id = "${server.host}:${server.port}:${response.user.id}",
                serverHost = server.host,
                serverPort = server.port,
                ntfyHost = ntfyHost.trim(),
                ntfyPort = ntfyPort,
                ntfyTopic = "tasksbase-user-${response.user.id}",
                ntfyUser = ntfyUser?.takeIf { it.isNotBlank() },
                ntfyPass = ntfyPass?.takeIf { it.isNotBlank() },
                token = response.token,
                user = response.user,
            )

            ServiceLocator.addOrUpdateAccount(account)
            runCatching { RealtimeService.start(ServiceLocator.appContext) }
            return account
        } catch (e: Exception) {
            // Restore the previous server so other accounts keep working.
            ServiceLocator.setLoginServer(previousServer)
            throw e
        }
    }

    /** Removes the active account (switches to another one if present). */
    suspend fun logout() {
        ServiceLocator.removeActiveAccount()
        runCatching { RealtimeService.stop(ServiceLocator.appContext) }
    }
}
