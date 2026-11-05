package com.tasksbase.chat.ui.chat

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.notifications.RealtimeService
import com.tasksbase.chat.ui.components.CenterError
import com.tasksbase.chat.ui.components.CenterProgress
import com.tasksbase.chat.ui.components.CenterText
import com.tasksbase.chat.ui.components.LetterAvatar
import com.tasksbase.chat.ui.theme.TgAccent
import com.tasksbase.chat.ui.theme.TgChatBg
import com.tasksbase.chat.ui.theme.TgSecondary
import com.tasksbase.chat.ui.util.avatarColorFor
import com.tasksbase.chat.ui.util.chatAvatarOwnerId
import com.tasksbase.chat.ui.util.chatAvatarText
import com.tasksbase.chat.ui.util.chatDisplayName
import com.tasksbase.chat.ui.util.chatPreview
import com.tasksbase.chat.ui.util.formatListTime
import com.tasksbase.chat.ui.util.friendlyError
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChatListScreen(
    onOpenChat: (ChatDto) -> Unit,
    onAddAccount: () -> Unit,
    onLoggedOut: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var chats by remember { mutableStateOf<List<ChatDto>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var showCreate by remember { mutableStateOf(false) }
    var users by remember { mutableStateOf<List<UserDto>>(emptyList()) }
    var accountMenu by remember { mutableStateOf(false) }

    val active = ServiceLocator.activeAccount

    LaunchedEffect(Unit) {
        while (isActive) {
            runCatching { ServiceLocator.chatRepository.myChats() }
                .onSuccess {
                    chats = it
                    error = null
                }
                .onFailure { if (chats.isEmpty()) error = friendlyError(it) }
            loading = false
            delay(15_000)
        }
    }

    LaunchedEffect(showCreate) {
        if (showCreate && users.isEmpty()) {
            runCatching { ServiceLocator.chatRepository.users() }.onSuccess { users = it }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Box {
                        Column(
                            modifier = Modifier.clickable { accountMenu = true },
                        ) {
                            Text(
                                text = active?.user?.fullName ?: "Чаты",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                            Text(
                                text = active?.display ?: "Выберите сервер",
                                fontSize = 11.sp,
                                color = TgSecondary,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                        }

                        DropdownMenu(
                            expanded = accountMenu,
                            onDismissRequest = { accountMenu = false },
                        ) {
                            ServiceLocator.accounts.forEach { account ->
                                DropdownMenuItem(
                                    text = {
                                        Column {
                                            Text(account.user.fullName)
                                            Text(
                                                text = account.display,
                                                fontSize = 11.sp,
                                                color = TgSecondary,
                                            )
                                        }
                                    },
                                    onClick = {
                                        accountMenu = false
                                        if (account.id != active?.id) {
                                            scope.launch {
                                                ServiceLocator.setActive(account.id)
                                                runCatching { RealtimeService.stop(ServiceLocator.appContext) }
                                                runCatching { RealtimeService.start(ServiceLocator.appContext) }
                                            }
                                        }
                                    },
                                )
                            }
                            HorizontalDivider()
                            DropdownMenuItem(
                                text = { Text("Добавить аккаунт") },
                                onClick = {
                                    accountMenu = false
                                    onAddAccount()
                                },
                            )
                            DropdownMenuItem(
                                text = { Text("Выйти") },
                                onClick = {
                                    accountMenu = false
                                    scope.launch {
                                        ServiceLocator.authRepository.logout()
                                        onLoggedOut()
                                    }
                                },
                            )
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = androidx.compose.ui.graphics.Color.White,
                ),
                actions = {
                    IconButton(onClick = { showCreate = true }) {
                        Icon(Icons.Default.Add, contentDescription = "Новый чат")
                    }
                },
            )
        },
    ) { padding ->
        Box(
            Modifier
                .padding(padding)
                .fillMaxSize()
                .background(TgChatBg),
        ) {
            when {
                loading && chats.isEmpty() -> CenterProgress()
                error != null && chats.isEmpty() -> CenterError(error!!)
                chats.isEmpty() -> CenterText("Чатов пока нет.\nНажмите «+», чтобы создать.")
                else -> LazyColumn(Modifier.fillMaxSize()) {
                    items(chats, key = { it.id }) { chat ->
                        ChatListRow(chat = chat, onClick = { onOpenChat(chat) })
                    }
                }
            }
        }
    }

    if (showCreate) {
        CreateChatDialog(
            users = users,
            onDismiss = { showCreate = false },
            onCreated = { created ->
                showCreate = false
                chats = listOf(created) + chats.filter { it.id != created.id }
                onOpenChat(created)
                scope.launch {
                    runCatching { chats = ServiceLocator.chatRepository.myChats() }
                }
            },
        )
    }
}

@Composable
private fun ChatListRow(chat: ChatDto, onClick: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .background(androidx.compose.ui.graphics.Color.White)
            .padding(horizontal = 12.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        LetterAvatar(
            text = chatAvatarText(chat),
            color = avatarColorFor(chatAvatarOwnerId(chat)),
            size = 50.dp,
        )
        Spacer(Modifier.width(12.dp))
        Column(Modifier.weight(1f)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = chatDisplayName(chat),
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 15.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.weight(1f, fill = false),
                )
                Text(
                    text = formatListTime(chat.lastMessage?.createdAt ?: chat.createdAt),
                    fontSize = 12.sp,
                    color = TgSecondary,
                )
            }
            Spacer(Modifier.width(2.dp))
            Text(
                text = chatPreview(chat),
                fontSize = 13.sp,
                color = TgSecondary,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        Spacer(Modifier.width(8.dp))
        if (chat.type == "channel") {
            Text("#", color = TgAccent, fontWeight = FontWeight.Bold)
        }
    }
}
