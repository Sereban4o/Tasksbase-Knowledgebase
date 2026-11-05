package com.tasksbase.chat.ui.chat

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Send
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.MessageDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session
import com.tasksbase.chat.ui.components.CenterError
import com.tasksbase.chat.ui.components.CenterProgress
import com.tasksbase.chat.ui.components.CenterText
import com.tasksbase.chat.ui.components.LetterAvatar
import com.tasksbase.chat.ui.theme.TgAccent
import com.tasksbase.chat.ui.theme.TgChatBg
import com.tasksbase.chat.ui.theme.TgOutgoing
import com.tasksbase.chat.ui.theme.TgSecondary
import com.tasksbase.chat.ui.theme.TgText
import com.tasksbase.chat.ui.util.avatarColorFor
import com.tasksbase.chat.ui.util.chatAvatarOwnerId
import com.tasksbase.chat.ui.util.chatAvatarText
import com.tasksbase.chat.ui.util.chatDisplayName
import com.tasksbase.chat.ui.util.chatSubtitle
import com.tasksbase.chat.ui.util.formatMessageTime
import com.tasksbase.chat.ui.util.friendlyError
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChatScreen(chat: ChatDto, onBack: () -> Unit) {
    val scope = rememberCoroutineScope()
    val myId = Session.currentUser?.id

    var messages by remember { mutableStateOf<List<MessageDto>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var draft by remember { mutableStateOf("") }
    var refreshKey by remember { mutableIntStateOf(0) }

    suspend fun refresh() {
        runCatching { ServiceLocator.chatRepository.messages(chat.id) }
            .onSuccess {
                messages = it
                error = null
            }
            .onFailure { if (messages.isEmpty()) error = friendlyError(it) }
        loading = false
    }

    // Initial load, read receipt and polling fallback.
    LaunchedEffect(chat.id) {
        ServiceLocator.chatRepository.markRead(chat.id)
        while (isActive) {
            refresh()
            delay(6_000)
        }
    }

    // Realtime refresh when a Message* event arrives.
    LaunchedEffect(refreshKey) {
        if (refreshKey > 0) refresh()
    }

    DisposableEffect(chat.id) {
        Session.activeChatId = chat.id
        val realtime = ServiceLocator.newRealtime()
        realtime.onMessageEvent = { refreshKey++ }
        realtime.connect()
        realtime.subscribe("private-chat.${chat.id}")
        onDispose {
            Session.activeChatId = null
            realtime.close()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Назад")
                    }
                },
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        LetterAvatar(
                            text = chatAvatarText(chat, myId),
                            color = avatarColorFor(chatAvatarOwnerId(chat, myId)),
                            size = 36.dp,
                        )
                        Spacer(Modifier.width(10.dp))
                        Column {
                            Text(
                                text = chatDisplayName(chat, myId),
                                fontSize = 16.sp,
                                fontWeight = FontWeight.SemiBold,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                            Text(text = chatSubtitle(chat), fontSize = 12.sp, color = TgSecondary)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.White),
            )
        },
    ) { padding ->
        Column(
            modifier = Modifier
                .padding(padding)
                .fillMaxSize()
                .background(TgChatBg),
        ) {
            Box(Modifier.weight(1f).fillMaxWidth()) {
                when {
                    loading && messages.isEmpty() -> CenterProgress()
                    error != null && messages.isEmpty() -> CenterError(error!!)
                    messages.isEmpty() -> CenterText("Сообщений пока нет.\nНапишите первым!")
                    else -> LazyColumn(
                        modifier = Modifier.fillMaxSize().padding(horizontal = 8.dp, vertical = 6.dp),
                        verticalArrangement = Arrangement.spacedBy(3.dp),
                    ) {
                        items(messages, key = { it.id }) { message ->
                            MessageBubble(message = message, isOwn = message.senderId == myId)
                        }
                    }
                }
            }

            Surface(color = Color.White, shadowElevation = 6.dp) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    OutlinedTextField(
                        value = draft,
                        onValueChange = { draft = it },
                        modifier = Modifier.weight(1f),
                        placeholder = { Text("Сообщение") },
                        maxLines = 4,
                    )
                    Spacer(Modifier.width(8.dp))
                    FloatingActionButton(
                        onClick = {
                            val text = draft.trim()
                            if (text.isEmpty()) return@FloatingActionButton
                            draft = ""
                            scope.launch {
                                runCatching { ServiceLocator.chatRepository.sendMessage(chat.id, text) }
                                    .onSuccess { refresh() }
                                    .onFailure { error = friendlyError(it) }
                            }
                        },
                        containerColor = TgAccent,
                        contentColor = Color.White,
                    ) {
                        Icon(Icons.Default.Send, contentDescription = "Отправить")
                    }
                }
            }
        }
    }
}

@Composable
private fun MessageBubble(message: MessageDto, isOwn: Boolean) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = if (isOwn) Arrangement.End else Arrangement.Start,
    ) {
        Column(
            modifier = Modifier
                .widthIn(max = 290.dp)
                .clip(RoundedCornerShape(12.dp))
                .background(if (isOwn) TgOutgoing else Color.White)
                .padding(horizontal = 10.dp, vertical = 7.dp),
        ) {
            if (!message.body.isNullOrBlank()) {
                Text(text = message.body, color = TgText, fontSize = 15.sp)
            }
            Text(
                text = formatMessageTime(message.createdAt),
                fontSize = 10.sp,
                color = TgSecondary,
                modifier = Modifier.align(Alignment.End),
            )
        }
    }
}
