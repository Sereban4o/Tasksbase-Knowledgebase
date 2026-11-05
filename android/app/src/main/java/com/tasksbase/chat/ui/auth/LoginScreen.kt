package com.tasksbase.chat.ui.auth

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.tasksbase.chat.data.local.ServerConfig
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.ui.theme.TgAccent
import com.tasksbase.chat.ui.theme.TgSecondary
import com.tasksbase.chat.ui.util.friendlyError
import kotlinx.coroutines.launch

@Composable
fun LoginScreen(onLoggedIn: () -> Unit) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var showServerDialog by remember { mutableStateOf(false) }
    var serverLabel by remember { mutableStateOf(ServiceLocator.server.display) }
    val scope = rememberCoroutineScope()

    fun submit() {
        if (loading) return
        error = null
        loading = true
        scope.launch {
            try {
                ServiceLocator.authRepository.login(email, password)
                onLoggedIn()
            } catch (e: Exception) {
                error = friendlyError(e)
            } finally {
                loading = false
            }
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text("Tasksbase Chat", fontSize = 28.sp, fontWeight = FontWeight.Bold, color = TgAccent)
        Spacer(Modifier.height(6.dp))
        TextButton(onClick = { showServerDialog = true }) {
            Text("Сервер: $serverLabel", fontSize = 12.sp, color = TgSecondary)
        }
        Spacer(Modifier.height(18.dp))

        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text("Email") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier.fillMaxWidth(),
        )
        Spacer(Modifier.height(12.dp))

        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Пароль") },
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier.fillMaxWidth(),
        )

        if (error != null) {
            Spacer(Modifier.height(8.dp))
            Text(error!!, color = MaterialTheme.colorScheme.error)
        }

        Spacer(Modifier.height(20.dp))

        Button(
            onClick = { submit() },
            enabled = !loading && email.isNotBlank() && password.isNotBlank(),
            modifier = Modifier.fillMaxWidth(),
        ) {
            if (loading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(18.dp),
                    color = Color.White,
                    strokeWidth = 2.dp,
                )
            } else {
                Text("Войти")
            }
        }
    }

    if (showServerDialog) {
        ServerSettingsDialog(
            initial = ServiceLocator.server,
            onDismiss = { showServerDialog = false },
            onSaved = { config ->
                serverLabel = config.display
                showServerDialog = false
            },
        )
    }
}

@Composable
private fun ServerSettingsDialog(
    initial: ServerConfig,
    onDismiss: () -> Unit,
    onSaved: (ServerConfig) -> Unit,
) {
    var host by remember { mutableStateOf(initial.host) }
    var port by remember { mutableStateOf(initial.port.toString()) }
    var error by remember { mutableStateOf<String?>(null) }
    var saving by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    AlertDialog(
        onDismissRequest = { if (!saving) onDismiss() },
        title = { Text("Адрес сервера") },
        text = {
            Column {
                OutlinedTextField(
                    value = host,
                    onValueChange = { host = it },
                    label = { Text("Адрес (домен или IP)") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(
                    value = port,
                    onValueChange = { value -> port = value.filter { it.isDigit() } },
                    label = { Text("Порт") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                if (error != null) {
                    Spacer(Modifier.height(8.dp))
                    Text(error!!, color = MaterialTheme.colorScheme.error, fontSize = 12.sp)
                }
                Spacer(Modifier.height(8.dp))
                Text(
                    "Например: 192.168.0.5 и 80. Без http:// и без пути.",
                    fontSize = 11.sp,
                    color = TgSecondary,
                )
            }
        },
        confirmButton = {
            TextButton(
                enabled = !saving,
                onClick = {
                    val normalizedHost = normalizeHost(host)
                    val parsedPort = port.toIntOrNull()

                    if (normalizedHost.isBlank()) {
                        error = "Укажите адрес сервера"
                    } else if (parsedPort == null || parsedPort !in 1..65535) {
                        error = "Порт должен быть от 1 до 65535"
                    } else {
                        saving = true
                        scope.launch {
                            ServiceLocator.saveServer(normalizedHost, parsedPort)
                            onSaved(ServerConfig(normalizedHost, parsedPort))
                        }
                    }
                },
            ) {
                Text(if (saving) "Сохранение..." else "Сохранить")
            }
        },
        dismissButton = {
            TextButton(enabled = !saving, onClick = onDismiss) { Text("Отмена") }
        },
    )
}

/** Accepts "host", "http://host/", "host:port" and returns a bare host. */
private fun normalizeHost(raw: String): String {
    return raw.trim()
        .removePrefix("http://")
        .removePrefix("https://")
        .substringBefore('/')
        .substringBefore('?')
        .substringBefore(':')
        .trim()
        .trimEnd('/')
}
