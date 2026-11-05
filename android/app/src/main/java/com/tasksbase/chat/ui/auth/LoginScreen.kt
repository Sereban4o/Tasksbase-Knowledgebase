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

    val initialServer = remember { ServiceLocator.server }
    var host by remember { mutableStateOf(initialServer.host) }
    var port by remember { mutableStateOf(initialServer.port.toString()) }
    var ntfyHost by remember { mutableStateOf("") }
    var ntfyPort by remember { mutableStateOf("8081") }

    val scope = rememberCoroutineScope()

    fun submit() {
        if (loading) return
        val parsedPort = port.toIntOrNull()
        if (parsedPort == null || parsedPort !in 1..65535) {
            error = "Некорректный порт сервера"
            return
        }
        val parsedNtfyPort = ntfyPort.toIntOrNull() ?: 8081

        error = null
        loading = true
        scope.launch {
            try {
                ServiceLocator.authRepository.login(
                    email = email,
                    password = password,
                    server = ServerConfig(normalizeHost(host), parsedPort),
                    ntfyHost = normalizeHost(ntfyHost),
                    ntfyPort = parsedNtfyPort,
                )
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
        Text("Чат", fontSize = 28.sp, fontWeight = FontWeight.Bold, color = TgAccent)
        Spacer(Modifier.height(6.dp))
        TextButton(onClick = { showServerDialog = true }) {
            Text(
                "Сервер: ${normalizeHost(host)}:$port" +
                    if (ntfyHost.isNotBlank()) "  ·  ntfy: ${normalizeHost(ntfyHost)}:$ntfyPort" else "",
                fontSize = 12.sp,
                color = TgSecondary,
            )
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
            initialHost = host,
            initialPort = port,
            initialNtfyHost = ntfyHost,
            initialNtfyPort = ntfyPort,
            onDismiss = { showServerDialog = false },
            onSaved = { newHost, newPort, newNtfyHost, newNtfyPort ->
                host = newHost
                port = newPort
                ntfyHost = newNtfyHost
                ntfyPort = newNtfyPort
                showServerDialog = false
            },
        )
    }
}

@Composable
private fun ServerSettingsDialog(
    initialHost: String,
    initialPort: String,
    initialNtfyHost: String,
    initialNtfyPort: String,
    onDismiss: () -> Unit,
    onSaved: (String, String, String, String) -> Unit,
) {
    var host by remember { mutableStateOf(initialHost) }
    var port by remember { mutableStateOf(initialPort) }
    var ntfyHost by remember { mutableStateOf(initialNtfyHost) }
    var ntfyPort by remember { mutableStateOf(initialNtfyPort) }
    var error by remember { mutableStateOf<String?>(null) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Адреса серверов") },
        text = {
            Column {
                OutlinedTextField(
                    value = host,
                    onValueChange = { host = it },
                    label = { Text("Сервер (IP или домен)") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(
                    value = port,
                    onValueChange = { value -> port = value.filter { it.isDigit() } },
                    label = { Text("Порт сервера") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(12.dp))
                OutlinedTextField(
                    value = ntfyHost,
                    onValueChange = { ntfyHost = it },
                    label = { Text("ntfy сервер (необязательно)") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(
                    value = ntfyPort,
                    onValueChange = { value -> ntfyPort = value.filter { it.isDigit() } },
                    label = { Text("Порт ntfy") },
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
                    "Например: сервер 212.109.16.56:80, ntfy 212.109.16.56:8081",
                    fontSize = 11.sp,
                    color = TgSecondary,
                )
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    val parsedPort = port.toIntOrNull()
                    if (normalizeHost(host).isBlank()) {
                        error = "Укажите адрес сервера"
                    } else if (parsedPort == null || parsedPort !in 1..65535) {
                        error = "Порт сервера: 1..65535"
                    } else if (ntfyHost.isNotBlank() && (ntfyPort.toIntOrNull() ?: 0) !in 1..65535) {
                        error = "Порт ntfy: 1..65535"
                    } else {
                        onSaved(normalizeHost(host), parsedPort.toString(), normalizeHost(ntfyHost), ntfyPort)
                    }
                },
            ) {
                Text("Готово")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Отмена") }
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
