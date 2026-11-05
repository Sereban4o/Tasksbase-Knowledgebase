package com.tasksbase.chat.ui.chat

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.ui.components.LetterAvatar
import com.tasksbase.chat.ui.util.avatarColorFor
import com.tasksbase.chat.ui.util.friendlyError
import com.tasksbase.chat.ui.util.initialsOf
import kotlinx.coroutines.launch

private val CHAT_TYPES = listOf("dm" to "Личный", "group" to "Группа", "channel" to "Канал")

@Composable
fun CreateChatDialog(
    users: List<UserDto>,
    onDismiss: () -> Unit,
    onCreated: (ChatDto) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var type by remember { mutableStateOf("channel") }
    var name by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    val selected = remember { mutableStateListOf<Int>() }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    val title = when (type) {
        "dm" -> "Новый чат"
        "group" -> "Новая группа"
        else -> "Новый канал"
    }

    AlertDialog(
        onDismissRequest = { if (!loading) onDismiss() },
        title = { Text(title) },
        text = {
            Column(
                modifier = Modifier
                    .heightIn(max = 420.dp)
                    .verticalScroll(rememberScrollState()),
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    CHAT_TYPES.forEach { (value, label) ->
                        FilterChip(
                            selected = type == value,
                            onClick = {
                                type = value
                                selected.clear()
                                error = null
                            },
                            label = { Text(label) },
                        )
                    }
                }

                if (type != "dm") {
                    Spacer(Modifier.height(12.dp))
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        label = { Text("Название") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    Spacer(Modifier.height(8.dp))
                    OutlinedTextField(
                        value = description,
                        onValueChange = { description = it },
                        label = { Text("Описание (необязательно)") },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }

                Spacer(Modifier.height(12.dp))
                Text(
                    if (type == "dm") "Выберите пользователя" else "Добавить участников",
                    fontWeight = FontWeight.Medium,
                )
                Spacer(Modifier.height(4.dp))

                users.forEach { user ->
                    val checked = selected.contains(user.id)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                if (type == "dm") {
                                    selected.clear()
                                    selected.add(user.id)
                                } else if (checked) {
                                    selected.remove(user.id)
                                } else {
                                    selected.add(user.id)
                                }
                            }
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Checkbox(checked = checked, onCheckedChange = null)
                        LetterAvatar(
                            text = initialsOf(user.firstName, user.lastName),
                            color = avatarColorFor(user.id),
                            size = 32.dp,
                            modifier = Modifier.padding(end = 8.dp),
                        )
                        Text(user.fullName)
                    }
                }

                if (error != null) {
                    Spacer(Modifier.height(8.dp))
                    Text(error!!, color = MaterialTheme.colorScheme.error)
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val trimmed = name.trim()
                    val validation = when {
                        type == "dm" && selected.isEmpty() -> "Выберите пользователя"
                        type != "dm" && trimmed.isEmpty() -> "Введите название"
                        else -> null
                    }
                    if (validation != null) {
                        error = validation
                        return@Button
                    }
                    loading = true
                    error = null
                    scope.launch {
                        try {
                            val chat = ServiceLocator.chatRepository.createChat(
                                type = type,
                                name = if (type == "dm") null else trimmed,
                                description = if (type == "dm") null else description.trim().ifBlank { null },
                                userIds = selected.toList(),
                            )
                            onCreated(chat)
                        } catch (e: Exception) {
                            error = friendlyError(e)
                        } finally {
                            loading = false
                        }
                    }
                },
                enabled = !loading,
            ) {
                if (loading) {
                    CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                } else {
                    Text("Создать")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = { if (!loading) onDismiss() }) { Text("Отмена") }
        },
    )
}
