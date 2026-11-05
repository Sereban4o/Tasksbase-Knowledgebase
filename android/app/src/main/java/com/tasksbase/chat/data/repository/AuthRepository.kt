package com.tasksbase.chat.data.repository

import com.tasksbase.chat.data.local.TokenStore
import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.LoginRequest
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session
import com.tasksbase.chat.notifications.RealtimeService

class AuthRepository(
    private val api: ApiService,
    private val tokenStore: TokenStore,
) {
    suspend fun login(email: String, password: String) {
        val response = api.login(LoginRequest(email.trim(), password))
        Session.token = response.token
        Session.currentUser = response.user
        tokenStore.save(response.token, response.user)
        runCatching { RealtimeService.start(ServiceLocator.appContext) }
    }

    suspend fun logout() {
        runCatching { tokenStore.clear() }
        Session.reset()
        runCatching { RealtimeService.stop(ServiceLocator.appContext) }
    }
}
