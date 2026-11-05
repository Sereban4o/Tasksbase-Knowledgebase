package com.tasksbase.chat

import android.app.Application
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class ChatApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)

        CoroutineScope(Dispatchers.IO).launch {
            ServiceLocator.loadServerConfig()
            val (token, user) = ServiceLocator.tokenStore.load()
            Session.token = token
            Session.currentUser = user
            ServiceLocator.sessionReady.value = true
        }
    }
}
