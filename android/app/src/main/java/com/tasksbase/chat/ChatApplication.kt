package com.tasksbase.chat

import android.app.Application
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.notifications.RealtimeService
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class ChatApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)

        CoroutineScope(Dispatchers.IO).launch {
            ServiceLocator.loadAccounts()
            ServiceLocator.sessionReady.value = true

            if (ServiceLocator.activeAccount != null) {
                runCatching { RealtimeService.start(this@ChatApplication) }
            }
        }
    }
}
