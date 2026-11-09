package com.tasksbase.chat.ui.chat

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.ui.components.ChatAvatar
import com.tasksbase.chat.ui.components.chatAvatarUrl
import com.tasksbase.chat.ui.theme.LocalChatPalette
import com.tasksbase.chat.ui.util.avatarColorFor
import com.tasksbase.chat.ui.util.chatAvatarOwnerId
import com.tasksbase.chat.ui.util.friendlyError
import com.tasksbase.chat.ui.util.initialsOf
import com.tasksbase.chat.ui.util.isChatAdmin
import com.tasksbase.chat.ui.util.memberRoleLabel
import com.tasksbase.chat.ui.util.userStatusLabel
import kotlinx.coroutines.launch

/** Telegram-like profile card for a user (DM) or group/channel with management actions. */
@Composable
fun ChatInfoDialog(
    chat: ChatDto,
    myId: Int?,
    users: List<UserDto>,
    onDismiss: () -> Unit,
    onChanged: () -> Unit,
) {
    val palette = LocalChatPalette.current
    val scope = rememberCoroutineScope()

    val isDm = chat.type == "dm"
    val isChannel = chat.type == "channel"
    val other = if (isDm) chat.members?.firstOrNull { it.id != myId } else null
    val title = if (isDm) (other?.fullName ?: "Личный чат")
    else (chat.name?.takeIf { it.isNotBlank() } ?: "Чат")
    val fallback = if (isDm) initialsOf(other?.firstName, other?.lastName, "?")
    else (chat.name?.firstOrNull()?.uppercase() ?: "@")

    val canManage = isChatAdmin(chat, myId)

    var nameDraft by remember { mutableStateOf(chat.name.orEmpty()) }
    var editingName by remember { mutableStateOf(false) }
    var showAddMembers by remember { mutableStateOf(false) }
    var confirmLeave by remember { mutableStateOf(false) }
    var confirmDelete by remember { mutableStateOf(false) }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    fun run(action: suspend () -> Unit) {
        loading = true
        error = null
        scope.launch {
            try {
                action()
                onChanged()
            } catch (e: Exception) {
                error = friendlyError(e)
            } finally {
                loading = false
            }
        }
    }

    AlertDialog(
        onDismissRequest = { if (!loading) onDismiss() },
        confirmButton = { TextButton(onClick = onDismiss) { Text("Закрыть") } },
        title = { Text(title) },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 440.dp)
                    .verticalScroll(rememberScrollState()),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                ChatAvatar(
                    avatarUrl = chatAvatarUrl(chat, myId),
                    fallbackText = fallback,
                    color = avatarColorFor(chatAvatarOwnerId(chat, myId)),
                    size = 96.dp,
                )
                Spacer(Modifier.height(12.dp))

                if (isDm) {
                    other?.email?.takeIf { it.isNotBlank() }?.let {
                        Text(it, color = palette.secondary, fontSize = 14.sp)
                    }
                    userStatusLabel(other?.status)?.takeIf { it.isNotBlank() }?.let {
                        Text(it, color = MaterialTheme.colorScheme.primary, fontSize = 13.sp)
                    }
                } else {
                    if (editingName) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            OutlinedTextField(
                                value = nameDraft,
                                onValueChange = { nameDraft = it },
                                singleLine = true,
                                modifier = Modifier.weight(1f),
                            )
                            TextButton(
                                onClick = {
                                    val trimmed = nameDraft.trim()
                                    editingName = false
                                    if (trimmed.isNotEmpty() && trimmed != chat.name) {
                                        run { ServiceLocator.chatRepository.renameChat(chat.id, trimmed) }
                                    }
                                },
                                enabled = !loading,
                            ) { Text("OK") }
                        }
                    } else {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(title, fontWeight = FontWeight.SemiBold, fontSize = 16.sp)
                            if (canManage) {
                                Spacer(Modifier.width(8.dp))
                                TextButton(onClick = { editingName = true }, enabled = !loading) { Text("Изменить") }
                            }
                        }
                    }

                    chat.description?.takeIf { it.isNotBlank() }?.let {
                        Spacer(Modifier.height(4.dp))
                        Text(it, color = palette.secondary, fontSize = 14.sp)
                    }

                    Spacer(Modifier.height(10.dp))
                    Text("${chat.members?.size ?: 0} участников", fontWeight = FontWeight.SemiBold)
                    Spacer(Modifier.height(6.dp))

                    chat.members.orEmpty().forEach { member ->
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            ChatAvatar(
                                avatarUrl = member.avatar,
                                fallbackText = initialsOf(member.firstName, member.lastName, "?"),
                                color = avatarColorFor(member.id),
                                size = 32.dp,
                            )
                            Spacer(Modifier.width(10.dp))
                            Column(Modifier.weight(1f)) {
                                Text(
                                    member.fullName + if (member.id == myId) " (вы)" else "",
                                    fontSize = 14.sp,
                                )
                                Text(
                                    memberRoleLabel(member.pivot?.role),
                                    fontSize = 11.sp,
                                    color = palette.secondary,
                                )
                            }
                        }
                    }

                    Spacer(Modifier.height(8.dp))
                    if (canManage) {
                        TextButton(onClick = { showAddMembers = true }, enabled = !loading) {
                            Text("Добавить участников")
                        }
                    }
                    TextButton(onClick = { confirmLeave = true }, enabled = !loading) {
                        Text(if (isChannel) "Покинуть канал" else "Покинуть группу")
                    }
                }

                Spacer(Modifier.height(4.dp))
                TextButton(onClick = { confirmDelete = true }, enabled = !loading) {
                    Text("Удалить чат", color = MaterialTheme.colorScheme.error)
                }

                if (error != null) {
                    Spacer(Modifier.height(8.dp))
                    Text(error!!, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
                }
            }
        },
        dismissButton = null,
    )

    if (showAddMembers) {
        AddMembersDialog(
            users = users,
            existingIds = chat.members.orEmpty().map { it.id }.toSet(),
            onDismiss = { showAddMembers = false },
        ) { ids ->
            run { ServiceLocator.chatRepository.addMembers(chat.id, ids) }
        }
    }

    if (confirmLeave) {
        AlertDialog(
            onDismissRequest = { confirmLeave = false },
            title = { Text(if (isChannel) "Покинуть канал?" else "Покинуть группу?") },
            text = { Text("Вы больше не будете получать сообщения из этого чата.") },
            confirmButton = {
                TextButton(onClick = {
                    confirmLeave = false
                    run { ServiceLocator.chatRepository.leaveChat(chat.id) }
                }) { Text("Покинуть") }
            },
            dismissButton = { TextButton(onClick = { confirmLeave = false }) { Text("Отмена") } },
        )
    }

    if (confirmDelete) {
        AlertDialog(
            onDismissRequest = { confirmDelete = false },
            title = { Text("Удалить чат?") },
            text = { Text("Чат будет удалён для всех участников. Это действие нельзя отменить.") },
            confirmButton = {
                TextButton(onClick = {
                    confirmDelete = false
                    run { ServiceLocator.chatRepository.deleteChat(chat.id) }
                }) { Text("Удалить") }
            },
            dismissButton = { TextButton(onClick = { confirmDelete = false }) { Text("Отмена") } },
        )
    }
}
