package com.tasksbase.chat.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.ui.auth.LoginScreen
import com.tasksbase.chat.ui.chat.ChatListScreen
import com.tasksbase.chat.ui.chat.ChatScreen
import com.tasksbase.chat.ui.theme.TgAccent

@Composable
fun AppRoot() {
    val ready by ServiceLocator.sessionReady.collectAsState()
    val active by ServiceLocator.activeAccountFlow.collectAsState()

    if (!ready) {
        Box(
            modifier = Modifier.fillMaxSize().background(Color.White),
            contentAlignment = Alignment.Center,
        ) {
            CircularProgressIndicator(color = TgAccent)
        }
        return
    }

    var showLogin by remember { mutableStateOf(active == null) }

    if (showLogin) {
        LoginScreen(onLoggedIn = { showLogin = false })
        return
    }

    // Re-key by account id so switching accounts recreates screens and reloads data.
    key(active?.id) {
        var selectedChat by remember { mutableStateOf<ChatDto?>(null) }
        val chat = selectedChat

        if (chat == null) {
            ChatListScreen(
                onOpenChat = { selectedChat = it },
                onAddAccount = { showLogin = true },
                onLoggedOut = { if (ServiceLocator.activeAccount == null) showLogin = true },
            )
        } else {
            ChatScreen(chat = chat, onBack = { selectedChat = null })
        }
    }
}
