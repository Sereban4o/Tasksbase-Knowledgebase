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
import androidx.compose.material3.MaterialTheme
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
import androidx.compose.ui.unit.dp
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.ui.components.LetterAvatar
import com.tasksbase.chat.ui.util.avatarColorFor
import com.tasksbase.chat.ui.util.friendlyError
import com.tasksbase.chat.ui.util.initialsOf
import kotlinx.coroutines.launch

/** Multi-select dialog for adding members to a group/channel. */
@Composable
fun AddMembersDialog(
    users: List<UserDto>,
    existingIds: Set<Int>,
    onDismiss: () -> Unit,
    onSubmit: suspend (List<Int>) -> Unit,
) {
    val scope = rememberCoroutineScope()
    val selected = remember { mutableStateListOf<Int>() }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    val available = users.filter { it.id !in existingIds }

    AlertDialog(
        onDismissRequest = { if (!loading) onDismiss() },
        title = { Text("Добавить участников") },
        text = {
            if (available.isEmpty()) {
                Text("Нет доступных пользователей")
            } else {
                Column(Modifier.heightIn(max = 420.dp).verticalScroll(rememberScrollState())) {
                    available.forEach { user ->
                        val checked = selected.contains(user.id)
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    if (checked) selected.remove(user.id) else selected.add(user.id)
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
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (selected.isEmpty()) return@Button
                    loading = true
                    error = null
                    scope.launch {
                        try {
                            onSubmit(selected.toList())
                            onDismiss()
                        } catch (e: Exception) {
                            error = friendlyError(e)
                        } finally {
                            loading = false
                        }
                    }
                },
                enabled = !loading && selected.isNotEmpty(),
            ) {
                if (loading) {
                    CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                } else {
                    Text("Добавить")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = { if (!loading) onDismiss() }) { Text("Отмена") }
        },
    )
}
