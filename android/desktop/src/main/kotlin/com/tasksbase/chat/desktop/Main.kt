package com.tasksbase.chat.desktop

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.produceState
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.toComposeImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Window
import androidx.compose.ui.window.application
import androidx.compose.ui.window.rememberWindowState
import com.google.gson.Gson
import com.google.gson.annotations.SerializedName
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.asRequestBody
import org.jetbrains.skia.Image as SkiaImage
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query
import java.io.File
import java.net.URL
import java.util.Base64
import java.util.concurrent.TimeUnit
import java.util.prefs.Preferences
import kotlin.system.exitProcess
import javax.swing.JFileChooser
import javax.swing.SwingUtilities
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.collectAsState
import kotlinx.coroutines.flow.MutableStateFlow
import okhttp3.FormBody
import okhttp3.Response
import okhttp3.WebSocket
import okhttp3.WebSocketListener
import org.json.JSONObject
import java.awt.Graphics2D
import java.awt.MenuItem
import java.awt.PopupMenu
import java.awt.RenderingHints
import java.awt.SystemTray
import java.awt.TrayIcon
import java.awt.image.BufferedImage

// ───────────────────────────── DTOs ─────────────────────────────

data class PivotDto(
    val role: String = "member",
    @SerializedName("last_read_at") val lastReadAt: String? = null,
)

data class UserDto(
    val id: Int = 0,
    val email: String? = null,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    val avatar: String? = null,
    val status: String? = null,
    val pivot: PivotDto? = null,
) {
    val fullName: String
        get() = listOfNotNull(firstName, lastName).joinToString(" ").trim()
            .ifBlank { email ?: "Пользователь" }

    val initials: String
        get() = listOfNotNull(firstName?.firstOrNull(), lastName?.firstOrNull())
            .joinToString("").uppercase().ifBlank { "?" }
}

data class FileDto(
    val id: Int = 0,
    @SerializedName("original_name") val originalName: String? = null,
    @SerializedName("mime_type") val mimeType: String? = null,
    val size: Long = 0,
)

data class MessageFileDto(
    val id: Int = 0,
    @SerializedName("file_id") val fileId: Int = 0,
    @SerializedName("mime_type") val mimeType: String? = null,
    val file: FileDto? = null,
) {
    val name: String get() = file?.originalName ?: "Файл"
    val isImage: Boolean get() = (file?.mimeType ?: mimeType)?.startsWith("image/") == true
}

data class MessageDto(
    val id: Int = 0,
    @SerializedName("chat_id") val chatId: Int = 0,
    @SerializedName("sender_id") val senderId: Int = 0,
    val body: String? = null,
    val type: String = "text",
    @SerializedName("created_at") val createdAt: String? = null,
    val sender: UserDto? = null,
    val files: List<MessageFileDto>? = null,
)

data class ChatDto(
    val id: Int = 0,
    @SerializedName("created_by") val createdBy: Int = 0,
    val name: String? = null,
    val type: String = "dm",
    val avatar: String? = null,
    val description: String? = null,
    val members: List<UserDto>? = null,
    @SerializedName("last_message") val lastMessage: MessageDto? = null,
)

data class PageDto<T>(val data: List<T> = emptyList())

data class LoginRequest(val email: String = "", val password: String = "")
data class LoginResponse(val token: String = "", val user: UserDto = UserDto())

data class SendMessageRequest(
    val body: String? = null,
    val type: String = "text",
    @SerializedName("file_ids") val fileIds: List<Int>? = null,
)

data class CreateChatRequest(
    val type: String = "dm",
    val name: String? = null,
    val description: String? = null,
    @SerializedName("user_ids") val userIds: List<Int> = emptyList(),
)

data class UpdateChatRequest(
    val name: String? = null,
    val description: String? = null,
)

data class AddMembersRequest(
    @SerializedName("user_ids") val userIds: List<Int>,
)

data class RemoveMemberRequest(
    @SerializedName("user_id") val userId: Int,
)

// ─────────────────────────── Account store ───────────────────────────

data class Account(
    val id: String = "",
    val host: String = "",
    val port: Int = 0,
    val ntfyHost: String = "",
    val ntfyPort: Int = 0,
    val topic: String = "",
    val ntfyUser: String? = null,
    val ntfyPass: String? = null,
    val token: String = "",
    val user: UserDto = UserDto(),
) {
    val display: String get() = "$host:$port"

    private val ntfyExplicit: Boolean get() = ntfyHost.isNotBlank()

    /** If ntfy is not configured, fall back to the main server host. */
    val effectiveNtfyHost: String get() = if (ntfyExplicit) ntfyHost else host

    val effectiveNtfyPort: Int get() = if (ntfyExplicit) ntfyPort else 8081

    val effectiveNtfyUser: String get() = ntfyUser?.takeIf { it.isNotBlank() } ?: "tasksbase"

    val effectiveNtfyPass: String get() = ntfyPass?.takeIf { it.isNotBlank() } ?: "tasksbase"
}

object Store {
    private val gson = Gson()
    private val prefs: Preferences = Preferences.userRoot().node("tasksbase_chat_desktop")

    var accounts: List<Account> = emptyList()
        private set
    var active: Account? = null
        private set

    fun load() {
        val raw = prefs.get("accounts", null)
        accounts = if (raw.isNullOrBlank()) emptyList() else runCatching {
            gson.fromJson(raw, Array<Account>::class.java).toList()
        }.getOrDefault(emptyList())
        val activeId = prefs.get("active", "")
        active = accounts.firstOrNull { it.id == activeId } ?: accounts.firstOrNull()
    }

    fun persist() {
        prefs.put("accounts", gson.toJson(accounts))
        prefs.put("active", active?.id ?: "")
    }

    fun upsert(account: Account) {
        accounts = accounts.filter { it.id != account.id } + account
        active = account
        persist()
    }

    fun setActive(account: Account) {
        active = account
        persist()
    }

    fun removeActive() {
        val current = active ?: return
        accounts = accounts.filter { it.id != current.id }
        active = accounts.firstOrNull()
        persist()
    }
}

// ───────────────────────────── Networking ─────────────────────────────

interface ApiService {
    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @GET("chat/my")
    suspend fun myChats(@Query("per_page") perPage: Int = 50): PageDto<ChatDto>

    @GET("chat/{id}/messages")
    suspend fun messages(@Path("id") chatId: Int, @Query("per_page") perPage: Int = 50): PageDto<MessageDto>

    @POST("chat/{id}/messages")
    suspend fun sendMessage(@Path("id") chatId: Int, @Body body: SendMessageRequest): MessageDto

    @GET("users/all")
    suspend fun users(): List<UserDto>

    @POST("chat")
    suspend fun createChat(@Body body: CreateChatRequest): ChatDto

    @GET("chat/{id}")
    suspend fun chat(@Path("id") chatId: Int): ChatDto

    @PATCH("chat/{id}")
    suspend fun updateChat(@Path("id") chatId: Int, @Body body: UpdateChatRequest): ChatDto

    @POST("chat/{id}/members")
    suspend fun addMembers(@Path("id") chatId: Int, @Body body: AddMembersRequest): ChatDto

    @POST("chat/{id}/leave")
    suspend fun leaveChat(@Path("id") chatId: Int)

    @DELETE("chat/{id}")
    suspend fun deleteChat(@Path("id") chatId: Int)
}

object Net {
    private val gson = Gson()

    @Volatile var host: String = "localhost"
    @Volatile var port: Int = 8000
    @Volatile var token: String? = null

    private val client: OkHttpClient = OkHttpClient.Builder()
        .addInterceptor { chain ->
            val original = chain.request()
            val url = original.url.newBuilder().scheme("http").host(host).port(port).build()
            val builder = original.newBuilder().url(url)
            token?.let { if (original.header("Authorization") == null) builder.header("Authorization", "Bearer $it") }
            chain.proceed(builder.build())
        }
        .connectTimeout(20, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    val api: ApiService = Retrofit.Builder()
        .baseUrl("http://localhost/api/v1/")
        .client(client)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()
        .create(ApiService::class.java)

    fun useServer(h: String, p: Int) {
        host = h
        port = p
        token = null
    }

    fun useAccount(account: Account) {
        host = account.host
        port = account.port
        token = account.token
    }

    fun upload(file: File): FileDto {
        val body = MultipartBody.Builder().setType(MultipartBody.FORM)
            .addFormDataPart(
                "file",
                file.name,
                file.asRequestBody("application/octet-stream".toMediaType()),
            )
            .build()
        val request = Request.Builder().url("http://localhost/api/v1/files/upload").post(body).build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) error("Ошибка загрузки: HTTP ${response.code}")
            return gson.fromJson(response.body!!.string(), FileDto::class.java)
        }
    }

    fun fileUrl(fileId: Int): String = "http://$host:$port/api/v1/files/$fileId"
}

// ───────────────────────────── App ─────────────────────────────

private val LocalIsDark = staticCompositionLocalOf { false }

private val Accent = Color(0xFF3390EC)
private val ChatBg = Color(0xFFE6EBEE)
private val BubbleOut = Color(0xFFEEFFDE)
private val Muted = Color(0xFF707579)

private val EMOJIS = listOf(
    "😀", "😁", "😂", "🤣", "😊", "😍", "😎", "🤔",
    "😢", "😡", "👍", "👎", "👏", "🙌", "🙏", "🤝",
    "🔥", "🎉", "❤️", "💔", "✅", "❌", "🚀", "⭐",
)

fun main() {
    Store.load()
    Settings.load()
    Store.active?.let { Net.useAccount(it) }
    if (Settings.autostart) Autostart.apply(true)

    var showWindow: () -> Unit = {}
    var quit: () -> Unit = {}

    val tray = setupTray(onShow = { showWindow() }, onExit = { quit() })
    Notifier.tray = tray

    // Start hidden in the tray when the user enabled it and an account exists.
    val windowVisible = mutableStateOf(!(Settings.startInTray && Store.active != null && tray != null))
    showWindow = { windowVisible.value = true }

    application {
        quit = {
            RealtimeController.stop()
            Notifier.tray?.let { runCatching { SystemTray.getSystemTray().remove(it) } }
            exitApplication()
        }

        Window(
            onCloseRequest = {
                if (Notifier.tray != null) windowVisible.value = false else quit()
            },
            visible = windowVisible.value,
            title = "Чат",
            state = rememberWindowState(width = 1040.dp, height = 780.dp),
        ) {
            LaunchedEffect(windowVisible.value) {
                if (windowVisible.value) {
                    window.toFront()
                }
            }
            val dark = Settings.darkThemeState.value
            MaterialTheme(
                colorScheme = if (dark) darkColorScheme(primary = Accent) else lightColorScheme(primary = Accent),
            ) {
                CompositionLocalProvider(LocalIsDark provides dark) {
                    App()
                }
            }
        }
    }
}

@Composable
private fun App() {
    var accounts by remember { mutableStateOf(Store.accounts) }
    var active by remember { mutableStateOf(Store.active) }
    var showLogin by remember { mutableStateOf(active == null) }
    var selectedChat by remember { mutableStateOf<ChatDto?>(null) }

    val deletedChat by RealtimeController.deletedChat.collectAsState()
    LaunchedEffect(deletedChat) {
        val id = deletedChat
        if (id != null) {
            if (selectedChat?.id == id) selectedChat = null
            RealtimeController.deletedChat.value = null
        }
    }

    DisposableEffect(active?.id) {
        val acc = active
        if (acc != null) RealtimeController.start(acc) else RealtimeController.stop()
        onDispose { RealtimeController.stop() }
    }

    if (showLogin) {
        LoginScreen { account ->
            accounts = Store.accounts
            active = account
            selectedChat = null
            showLogin = false
        }
        return
    }

    key(active?.id) {
        MainScreen(
            active = active,
            accounts = accounts,
            selectedChat = selectedChat,
            onSwitch = { account ->
                Store.setActive(account)
                Net.useAccount(account)
                active = account
                selectedChat = null
            },
            onAddAccount = { showLogin = true },
            onLogout = {
                Store.removeActive()
                accounts = Store.accounts
                active = Store.active
                active?.let { Net.useAccount(it) }
                selectedChat = null
                if (active == null) showLogin = true
            },
            onSelectChat = { selectedChat = it },
            onBack = { selectedChat = null },
        )
    }
}

@Composable
private fun LoginScreen(onLoggedIn: (Account) -> Unit) {
    var host by remember { mutableStateOf(Store.active?.host ?: "localhost") }
    var port by remember { mutableStateOf((Store.active?.port ?: 8000).toString()) }
    var ntfyHost by remember { mutableStateOf(Store.active?.ntfyHost ?: "") }
    var ntfyPort by remember { mutableStateOf((Store.active?.ntfyPort ?: 8081).toString()) }
    var ntfyUser by remember { mutableStateOf(Store.active?.ntfyUser ?: "") }
    var ntfyPass by remember { mutableStateOf(Store.active?.ntfyPass ?: "") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background).padding(32.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text("Чат", fontSize = 30.sp, fontWeight = FontWeight.Bold, color = Accent)
        Spacer(Modifier.size(4.dp))
        Text("Windows", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(Modifier.size(24.dp))

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedTextField(host, { host = it }, label = { Text("Сервер") }, singleLine = true, modifier = Modifier.width(280.dp))
            OutlinedTextField(port, { v -> port = v.filter { it.isDigit() } }, label = { Text("Порт") }, singleLine = true, modifier = Modifier.width(120.dp))
        }
        Spacer(Modifier.size(8.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedTextField(ntfyHost, { ntfyHost = it }, label = { Text("ntfy (необязательно)") }, singleLine = true, modifier = Modifier.width(280.dp))
            OutlinedTextField(ntfyPort, { v -> ntfyPort = v.filter { it.isDigit() } }, label = { Text("Порт ntfy") }, singleLine = true, modifier = Modifier.width(120.dp))
        }
        Spacer(Modifier.size(8.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedTextField(ntfyUser, { ntfyUser = it }, label = { Text("ntfy пользователь") }, singleLine = true, modifier = Modifier.width(190.dp))
            OutlinedTextField(ntfyPass, { ntfyPass = it }, label = { Text("ntfy пароль") }, singleLine = true, visualTransformation = PasswordVisualTransformation(), modifier = Modifier.width(210.dp))
        }
        Spacer(Modifier.size(12.dp))
        OutlinedTextField(
            email, { email = it },
            label = { Text("Email") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier.width(408.dp),
        )
        Spacer(Modifier.size(8.dp))
        OutlinedTextField(
            password, { password = it },
            label = { Text("Пароль") },
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier.width(408.dp),
        )

        if (error != null) {
            Spacer(Modifier.size(8.dp))
            Text(error!!, color = MaterialTheme.colorScheme.error)
        }

        Spacer(Modifier.size(16.dp))
        Button(
            onClick = {
                if (loading) return@Button
                val p = port.toIntOrNull() ?: 80
                val np = ntfyPort.toIntOrNull() ?: 8081
                error = null
                loading = true
                scope.launch {
                    try {
                        val account = withContext(Dispatchers.IO) {
                            Net.useServer(host.trim(), p)
                            val response = Net.api.login(LoginRequest(email.trim(), password))
                            Account(
                                id = "${host.trim()}:$p:${response.user.id}",
                                host = host.trim(),
                                port = p,
                                ntfyHost = ntfyHost.trim(),
                                ntfyPort = np,
                                topic = "tasksbase-user-${response.user.id}",
                                ntfyUser = ntfyUser.trim().ifBlank { null },
                                ntfyPass = ntfyPass.ifBlank { null },
                                token = response.token,
                                user = response.user,
                            )
                        }
                        Store.upsert(account)
                        Net.useAccount(account)
                        onLoggedIn(account)
                    } catch (e: Exception) {
                        error = e.message ?: "Ошибка входа"
                    } finally {
                        loading = false
                    }
                }
            },
            enabled = !loading && email.isNotBlank() && password.isNotBlank(),
            modifier = Modifier.width(408.dp),
        ) {
            if (loading) {
                CircularProgressIndicator(modifier = Modifier.size(18.dp), color = Color.White, strokeWidth = 2.dp)
            } else {
                Text("Войти")
            }
        }
    }
}

@Composable
private fun MainScreen(
    active: Account?,
    accounts: List<Account>,
    selectedChat: ChatDto?,
    onSwitch: (Account) -> Unit,
    onAddAccount: () -> Unit,
    onLogout: () -> Unit,
    onSelectChat: (ChatDto) -> Unit,
    onBack: () -> Unit,
) {
    var menuOpen by remember { mutableStateOf(false) }
    var showSettings by remember { mutableStateOf(false) }

    val scope = rememberCoroutineScope()
    var updateInfo by remember { mutableStateOf<DesktopUpdate?>(null) }

    LaunchedEffect(active?.id) {
        runCatching { withContext(Dispatchers.IO) { Updater.check() } }
            .getOrNull()
            ?.let { updateInfo = it }
    }

    Column(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.surface)) {
        Surface(color = MaterialTheme.colorScheme.surface, shadowElevation = 2.dp) {
            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Box {
                    Column(Modifier.clickable { menuOpen = true }) {
                        Text(active?.user?.fullName ?: "Аккаунт", fontWeight = FontWeight.SemiBold, fontSize = 16.sp)
                        Text(active?.display ?: "", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    DropdownMenu(expanded = menuOpen, onDismissRequest = { menuOpen = false }) {
                        accounts.forEach { account ->
                            DropdownMenuItem(
                                text = {
                                    Column {
                                        Text(account.user.fullName)
                                        Text(account.display, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                },
                                onClick = {
                                    menuOpen = false
                                    if (account.id != active?.id) onSwitch(account)
                                },
                            )
                        }
                        HorizontalDivider()
                        DropdownMenuItem(text = { Text("Добавить аккаунт") }, onClick = { menuOpen = false; onAddAccount() })
                        DropdownMenuItem(text = { Text("Настройки...") }, onClick = { menuOpen = false; showSettings = true })
                        DropdownMenuItem(
                            text = { Text("Проверить обновления") },
                            onClick = {
                                menuOpen = false
                                scope.launch {
                                    val info = withContext(Dispatchers.IO) {
                                        runCatching { Updater.check() }.getOrNull()
                                    }
                                    if (info == null) {
                                        Notifier.notify("Обновления", "У вас последняя версия")
                                    } else {
                                        updateInfo = info
                                    }
                                }
                            },
                        )
                        DropdownMenuItem(text = { Text("Выйти") }, onClick = { menuOpen = false; onLogout() })
                    }
                }
                Spacer(Modifier.weight(1f))
                if (selectedChat != null) {
                    TextButton(onClick = onBack) { Text("← Назад") }
                }
            }
        }

        HorizontalDivider()

        Box(Modifier.weight(1f).fillMaxWidth()) {
            if (selectedChat == null) {
                ChatList(active, onSelectChat)
            } else {
                ChatView(selectedChat, active, onBack)
            }
        }
    }

    if (showSettings) {
        SettingsDialog(onClose = { showSettings = false })
    }

    val pendingUpdate = updateInfo
    if (pendingUpdate != null) {
        AlertDialog(
            onDismissRequest = { updateInfo = null },
            title = { Text("Доступно обновление ${pendingUpdate.version}") },
            text = {
                Column {
                    if (!pendingUpdate.notes.isNullOrBlank()) Text(pendingUpdate.notes.orEmpty())
                    Text("Установить сейчас?")
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    val current = pendingUpdate
                    updateInfo = null
                    scope.launch {
                        runCatching {
                            val file = withContext(Dispatchers.IO) { Updater.download(current) }
                            Updater.install(file)
                            exitProcess(0)
                        }.onFailure {
                            Notifier.notify("Обновление", "Ошибка: ${it.message}")
                        }
                    }
                }) { Text("Обновить") }
            },
            dismissButton = { TextButton(onClick = { updateInfo = null }) { Text("Позже") } },
        )
    }
}

@Composable
private fun ChatList(active: Account?, onOpen: (ChatDto) -> Unit) {
    var chats by remember { mutableStateOf<List<ChatDto>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }

    val tick by RealtimeController.tick.collectAsState()

    LaunchedEffect(tick) {
        runCatching {
            withContext(Dispatchers.IO) { Net.api.myChats().data }
        }.onSuccess { chats = it; error = null; loading = false }
    }

    LaunchedEffect(active?.id) {
        while (isActive) {
            runCatching {
                withContext(Dispatchers.IO) { Net.api.myChats().data }
            }
                .onSuccess { chats = it; error = null; loading = false }
                .onFailure { if (chats.isEmpty()) error = it.message; loading = false }
            delay(15_000)
        }
    }

    Box(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.surfaceVariant)) {
        when {
            loading && chats.isEmpty() -> CenterMsg("Загрузка...")
            error != null && chats.isEmpty() -> CenterMsg(error!!)
            chats.isEmpty() -> CenterMsg("Чатов пока нет")
            else -> LazyColumn(Modifier.fillMaxSize()) {
                items(chats, key = { it.id }) { chat ->
                    Row(
                        modifier = Modifier.fillMaxWidth()
                            .clickable { onOpen(chat) }
                            .background(MaterialTheme.colorScheme.surface)
                            .padding(horizontal = 16.dp, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        InitialsAvatar(
                            text = chatAvatarText(chat, active?.user?.id),
                            color = avatarColor(chatAvatarOwnerId(chat, active?.user?.id)),
                            size = 44,
                        )
                        Spacer(Modifier.width(12.dp))
                        Column(Modifier.weight(1f)) {
                            Text(
                                text = chatTitle(chat, active?.user?.id),
                                fontWeight = FontWeight.SemiBold,
                                fontSize = 15.sp,
                            )
                            Text(
                                text = chat.lastMessage?.body ?: "Нет сообщений",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        Text(timeOf(chat.lastMessage?.createdAt), fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    HorizontalDivider()
                }
            }
        }
    }
}

@Composable
private fun ChatView(chat: ChatDto, active: Account?, onBack: () -> Unit) {
    var messages by remember { mutableStateOf<List<MessageDto>>(emptyList()) }
    var draft by remember { mutableStateOf("") }
    var showEmoji by remember { mutableStateOf(false) }
    var sending by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var showInfo by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    suspend fun refresh() {
        runCatching { withContext(Dispatchers.IO) { Net.api.messages(chat.id).data } }
            .onSuccess { messages = it.asReversed() }
    }

    val tick by RealtimeController.tick.collectAsState()

    LaunchedEffect(tick, chat.id) { refresh() }

    LaunchedEffect(chat.id) {
        while (isActive) {
            refresh()
            delay(5_000)
        }
    }

    DisposableEffect(chat.id) {
        RealtimeController.activeChatId = chat.id
        onDispose {
            if (RealtimeController.activeChatId == chat.id) RealtimeController.activeChatId = null
        }
    }

    fun sendText() {
        val text = draft.trim()
        if (text.isEmpty()) return
        draft = ""
        scope.launch {
            runCatching {
                withContext(Dispatchers.IO) { Net.api.sendMessage(chat.id, SendMessageRequest(text)) }
            }.onSuccess { refresh() }.onFailure { error = it.message }
        }
    }

    fun sendAttachment() {
        val file = pickFile() ?: return
        sending = true
        scope.launch {
            runCatching {
                val isImage = file.extension.lowercase() in listOf("png", "jpg", "jpeg", "gif", "webp", "bmp")
                val uploaded = withContext(Dispatchers.IO) { Net.upload(file) }
                withContext(Dispatchers.IO) {
                    Net.api.sendMessage(
                        chat.id,
                        SendMessageRequest(null, if (isImage) "image" else "file", listOf(uploaded.id)),
                    )
                }
            }.onSuccess { refresh() }.onFailure { error = it.message }
            sending = false
        }
    }

    Column(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.surfaceVariant)) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(MaterialTheme.colorScheme.surface)
                .clickable { showInfo = true }
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            InitialsAvatar(
                text = chatAvatarText(chat, active?.user?.id),
                color = avatarColor(chatAvatarOwnerId(chat, active?.user?.id)),
                size = 32,
            )
            Spacer(Modifier.width(10.dp))
            Text(
                text = chatTitle(chat, active?.user?.id),
                fontWeight = FontWeight.SemiBold,
                fontSize = 15.sp,
            )
        }

        Box(Modifier.weight(1f).fillMaxWidth()) {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp, vertical = 8.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp),
            ) {
                items(messages, key = { it.id }) { message ->
                    MessageBubble(message, message.senderId == active?.user?.id)
                }
            }
        }

        if (error != null) {
            Text(error!!, color = MaterialTheme.colorScheme.error, fontSize = 12.sp, modifier = Modifier.padding(horizontal = 12.dp))
        }

        Surface(color = MaterialTheme.colorScheme.surface, shadowElevation = 4.dp) {
            Column {
                if (showEmoji) {
                    LazyRow(Modifier.fillMaxWidth().padding(4.dp)) {
                        items(EMOJIS) { emoji ->
                            TextButton(onClick = { draft += emoji }) { Text(emoji, fontSize = 18.sp) }
                        }
                    }
                }
                Row(
                    modifier = Modifier.fillMaxWidth().padding(8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    TextButton(onClick = { showEmoji = !showEmoji }) { Text("😊", fontSize = 18.sp) }
                    TextButton(onClick = { sendAttachment() }, enabled = !sending) { Text("📎", fontSize = 18.sp) }
                    OutlinedTextField(
                        value = draft,
                        onValueChange = { draft = it },
                        modifier = Modifier.weight(1f),
                        placeholder = { Text("Сообщение") },
                        singleLine = false,
                        maxLines = 4,
                    )
                    Spacer(Modifier.width(8.dp))
                    Button(onClick = { sendText() }, enabled = draft.isNotBlank()) { Text("➤") }
                }
            }
        }
    }

    if (showInfo) {
        ChatInfoDialogDesktop(
            chat = chat,
            active = active,
            onDismiss = { showInfo = false },
            onChanged = {
                showInfo = false
                RealtimeController.tick.value++
                onBack()
            },
        )
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
                .widthIn(max = 460.dp)
                .background(if (isOwn) (if (LocalIsDark.current) Color(0xFF1F3B2A) else BubbleOut) else MaterialTheme.colorScheme.surface, RoundedCornerShape(12.dp))
                .padding(horizontal = 10.dp, vertical = 7.dp),
        ) {
            message.files?.forEach { mf ->
                if (mf.isImage) {
                    val url = Net.fileUrl(if (mf.fileId != 0) mf.fileId else (mf.file?.id ?: 0))
                    val bmp by produceState<ImageBitmap?>(initialValue = null, url) {
                        value = withContext(Dispatchers.IO) { loadBitmap(url) }
                    }
                    val image = bmp
                    if (image != null) {
                        Image(
                            bitmap = image,
                            contentDescription = mf.name,
                            modifier = Modifier.widthIn(max = 320.dp).heightIn(max = 260.dp).padding(bottom = 4.dp),
                        )
                    } else {
                        Text("🖼 ${mf.name}", fontSize = 13.sp, color = Accent)
                    }
                } else {
                    Text("📎 ${mf.name}", fontSize = 13.sp, color = Accent)
                }
            }
            if (!message.body.isNullOrBlank()) {
                Text(message.body!!, fontSize = 15.sp)
            }
            Text(
                text = timeOf(message.createdAt),
                fontSize = 10.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.align(Alignment.End),
            )
        }
    }
}

@Composable
private fun CenterMsg(text: String) {
    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Text(text, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

private fun chatTitle(chat: ChatDto, myId: Int?): String {
    if (!chat.name.isNullOrBlank()) return chat.name!!
    val other = chat.members?.firstOrNull { it.id != myId } ?: chat.members?.firstOrNull()
    return other?.fullName ?: "Чат"
}

private val AVATAR_COLORS = listOf(
    Color(0xFFFF6B6B), Color(0xFF4ECDC4), Color(0xFF45B7D1), Color(0xFF96CEB4),
    Color(0xFFFFEAA7), Color(0xFFDDA0DD), Color(0xFF98D8C8), Color(0xFFF7DC6F),
    Color(0xFFBB8FCE), Color(0xFF85C1E9),
)

private fun avatarColor(id: Int?): Color {
    val value = id ?: 0
    return AVATAR_COLORS[((value % AVATAR_COLORS.size) + AVATAR_COLORS.size) % AVATAR_COLORS.size]
}

private fun chatAvatarText(chat: ChatDto, myId: Int?): String {
    if (!chat.name.isNullOrBlank()) return chat.name!!.first().uppercase()
    if (chat.type == "dm") {
        val other = chat.members?.firstOrNull { it.id != myId }
        return other?.initials ?: "?"
    }
    return if (chat.type == "channel") "#" else "@"
}

private fun chatAvatarOwnerId(chat: ChatDto, myId: Int?): Int? =
    if (chat.type == "dm") chat.members?.firstOrNull { it.id != myId }?.id else chat.id

private fun isChatAdmin(chat: ChatDto, myId: Int?): Boolean {
    val role = chat.members?.firstOrNull { it.id == myId }?.pivot?.role
    if (role != null) return role == "admin"
    return chat.createdBy == myId
}

private fun memberRoleLabel(role: String?): String = when (role) {
    "admin" -> "Администратор"
    "muted" -> "Заглушён"
    else -> "Участник"
}

private fun userStatusLabel(status: String?): String = when (status) {
    "active" -> "Активен"
    "suspended" -> "Заблокирован"
    "pending" -> "Ожидает подтверждения"
    else -> ""
}

@Composable
private fun InitialsAvatar(text: String, color: Color, size: Int) {
    Box(
        modifier = Modifier.size(size.dp).clip(CircleShape).background(color),
        contentAlignment = Alignment.Center,
    ) {
        Text(text, color = Color.White, fontWeight = FontWeight.SemiBold, fontSize = (size * 0.4f).sp)
    }
}

@Composable
private fun ChatInfoDialogDesktop(
    chat: ChatDto,
    active: Account?,
    onDismiss: () -> Unit,
    onChanged: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    val myId = active?.user?.id
    val isDm = chat.type == "dm"
    val isChannel = chat.type == "channel"
    val canManage = isChatAdmin(chat, myId)
    val other = if (isDm) chat.members?.firstOrNull { it.id != myId } else null
    val title = if (isDm) (other?.fullName ?: "Личный чат")
    else (chat.name?.takeIf { it.isNotBlank() } ?: "Чат")

    var users by remember { mutableStateOf<List<UserDto>>(emptyList()) }
    var nameDraft by remember { mutableStateOf(chat.name.orEmpty()) }
    var editingName by remember { mutableStateOf(false) }
    var showAdd by remember { mutableStateOf(false) }
    var selected by remember { mutableStateOf<Set<Int>>(emptySet()) }
    var confirmLeave by remember { mutableStateOf(false) }
    var confirmDelete by remember { mutableStateOf(false) }
    var busy by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        runCatching { withContext(Dispatchers.IO) { Net.api.users() } }.onSuccess { users = it }
    }

    fun run(action: suspend () -> Unit) {
        busy = true
        error = null
        scope.launch {
            try {
                action()
                onChanged()
            } catch (e: Exception) {
                error = e.message ?: "Ошибка"
            } finally {
                busy = false
            }
        }
    }

    AlertDialog(
        onDismissRequest = { if (!busy) onDismiss() },
        confirmButton = { TextButton(onClick = onDismiss) { Text("Закрыть") } },
        title = { Text(title) },
        text = {
            Column(
                modifier = Modifier.widthIn(max = 460.dp).heightIn(max = 460.dp).verticalScroll(rememberScrollState()),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                InitialsAvatar(
                    text = if (isDm) (other?.initials ?: "?") else (chat.name?.firstOrNull()?.uppercase() ?: "@"),
                    color = avatarColor(chatAvatarOwnerId(chat, myId)),
                    size = 72,
                )
                Spacer(Modifier.size(10.dp))

                if (isDm) {
                    other?.email?.takeIf { it.isNotBlank() }?.let {
                        Text(it, color = Muted, fontSize = 13.sp)
                    }
                    userStatusLabel(other?.status).takeIf { it.isNotBlank() }?.let {
                        Text(it, color = Accent, fontSize = 12.sp)
                    }
                } else {
                    if (editingName) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            OutlinedTextField(nameDraft, { nameDraft = it }, singleLine = true, modifier = Modifier.weight(1f))
                            TextButton(
                                onClick = {
                                    val trimmed = nameDraft.trim()
                                    editingName = false
                                    if (trimmed.isNotEmpty() && trimmed != chat.name) {
                                        run { Net.api.updateChat(chat.id, UpdateChatRequest(name = trimmed)) }
                                    }
                                },
                                enabled = !busy,
                            ) { Text("OK") }
                        }
                    } else {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(title, fontWeight = FontWeight.SemiBold, fontSize = 16.sp)
                            if (canManage) {
                                Spacer(Modifier.size(8.dp))
                                TextButton(onClick = { editingName = true }, enabled = !busy) { Text("Изменить") }
                            }
                        }
                    }

                    chat.description?.takeIf { it.isNotBlank() }?.let {
                        Spacer(Modifier.size(4.dp))
                        Text(it, color = Muted, fontSize = 13.sp)
                    }

                    Spacer(Modifier.size(10.dp))
                    Text("${chat.members?.size ?: 0} участников", fontWeight = FontWeight.SemiBold)
                    Spacer(Modifier.size(6.dp))

                    chat.members.orEmpty().forEach { member ->
                        Row(Modifier.fillMaxWidth().padding(vertical = 4.dp), verticalAlignment = Alignment.CenterVertically) {
                            InitialsAvatar(text = member.initials, color = avatarColor(member.id), size = 30)
                            Spacer(Modifier.size(10.dp))
                            Column(Modifier.weight(1f)) {
                                Text(member.fullName + if (member.id == myId) " (вы)" else "", fontSize = 14.sp)
                                Text(memberRoleLabel(member.pivot?.role), fontSize = 11.sp, color = Muted)
                            }
                        }
                    }

                    Spacer(Modifier.size(8.dp))
                    if (canManage) {
                        TextButton(onClick = { showAdd = true }, enabled = !busy) { Text("Добавить участников") }
                    }
                    TextButton(onClick = { confirmLeave = true }, enabled = !busy) {
                        Text(if (isChannel) "Покинуть канал" else "Покинуть группу")
                    }
                }

                Spacer(Modifier.size(4.dp))
                TextButton(onClick = { confirmDelete = true }, enabled = !busy) {
                    Text("Удалить чат", color = MaterialTheme.colorScheme.error)
                }

                if (error != null) {
                    Spacer(Modifier.size(8.dp))
                    Text(error!!, color = MaterialTheme.colorScheme.error, fontSize = 12.sp)
                }
            }
        },
    )

    if (showAdd) {
        val existing = chat.members.orEmpty().map { it.id }.toSet()
        val available = users.filter { it.id !in existing }
        AlertDialog(
            onDismissRequest = { showAdd = false },
            title = { Text("Добавить участников") },
            text = {
                if (available.isEmpty()) {
                    Text("Нет доступных пользователей")
                } else {
                    LazyColumn(Modifier.heightIn(max = 360.dp)) {
                        items(available, key = { it.id }) { user ->
                            Row(
                                modifier = Modifier.fillMaxWidth().clickable {
                                    selected = if (selected.contains(user.id)) selected - user.id else selected + user.id
                                }.padding(vertical = 6.dp),
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                Checkbox(checked = selected.contains(user.id), onCheckedChange = null)
                                InitialsAvatar(text = user.initials, color = avatarColor(user.id), size = 28)
                                Spacer(Modifier.size(8.dp))
                                Text(user.fullName)
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val ids = selected.toList()
                        if (ids.isEmpty()) return@Button
                        showAdd = false
                        selected = emptySet()
                        run { Net.api.addMembers(chat.id, AddMembersRequest(ids)) }
                    },
                    enabled = !busy && selected.isNotEmpty(),
                ) { Text("Добавить") }
            },
            dismissButton = { TextButton(onClick = { showAdd = false; selected = emptySet() }) { Text("Отмена") } },
        )
    }

    if (confirmLeave) {
        AlertDialog(
            onDismissRequest = { confirmLeave = false },
            title = { Text(if (isChannel) "Покинуть канал?" else "Покинуть группу?") },
            text = { Text("Вы больше не будете получать сообщения из этого чата.") },
            confirmButton = {
                TextButton(onClick = {
                    confirmLeave = false
                    run { Net.api.leaveChat(chat.id) }
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
                    run { Net.api.deleteChat(chat.id) }
                }) { Text("Удалить") }
            },
            dismissButton = { TextButton(onClick = { confirmDelete = false }) { Text("Отмена") } },
        )
    }
}

private fun timeOf(iso: String?): String {
    if (iso.isNullOrBlank()) return ""
    return runCatching { iso.substringAfter('T').take(5) }.getOrDefault("")
}

private fun loadBitmap(url: String): ImageBitmap? = runCatching {
    val bytes = URL(url).readBytes()
    SkiaImage.makeFromEncoded(bytes).toComposeImageBitmap()
}.getOrNull()

private fun pickFile(): File? {
    var result: File? = null
    try {
        SwingUtilities.invokeAndWait {
            val chooser = JFileChooser()
            if (chooser.showOpenDialog(null) == JFileChooser.APPROVE_OPTION) {
                result = chooser.selectedFile
            }
        }
    } catch (_: Throwable) {
    }
    return result
}

// ───────────────────────────── Tray & notifications ─────────────────────────────

private object Notifier {
    @Volatile
    var tray: TrayIcon? = null

    fun notify(title: String, body: String) {
        val t = tray ?: run {
            println("[notify] $title: $body")
            return
        }
        runCatching {
            t.displayMessage(
                title.ifBlank { "Чат" },
                body.ifBlank { "Новое сообщение" },
                TrayIcon.MessageType.INFO,
            )
        }
    }
}

private fun setupTray(onShow: () -> Unit, onExit: () -> Unit): TrayIcon? {
    if (!SystemTray.isSupported()) return null
    return runCatching {
        val image = BufferedImage(16, 16, BufferedImage.TYPE_INT_ARGB)
        val g: Graphics2D = image.createGraphics()
        g.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON)
        g.color = java.awt.Color(0x33, 0x90, 0xEC)
        g.fillOval(0, 0, 16, 16)
        g.color = java.awt.Color.WHITE
        g.drawString("C", 5, 12)
        g.dispose()

        val popup = PopupMenu()
        val open = MenuItem("Открыть")
        open.addActionListener { onShow() }
        val exit = MenuItem("Выход")
        exit.addActionListener { onExit() }
        popup.add(open)
        popup.addSeparator()
        popup.add(exit)

        val icon = TrayIcon(image, "Чат", popup)
        icon.isImageAutoSize = true
        icon.addActionListener { onShow() }
        SystemTray.getSystemTray().add(icon)
        icon
    }.getOrNull()
}

// ───────────────────────────── Realtime (Reverb / Pusher) ─────────────────────────────

private class RealtimeClient(
    private val host: String,
    private val port: Int,
    private val key: String,
    private val authUrl: String,
    private val tokenProvider: () -> String?,
) {
    private val client = OkHttpClient.Builder()
        .pingInterval(30, TimeUnit.SECONDS)
        .build()

    private val scope = kotlinx.coroutines.CoroutineScope(Dispatchers.IO)
    private var reconnectJob: kotlinx.coroutines.Job? = null

    private var socket: WebSocket? = null
    private var socketId: String? = null

    @Volatile
    private var closed = false

    private val pending = mutableSetOf<String>()
    private val subscribed = mutableSetOf<String>()

    var onMessageEvent: ((JSONObject) -> Unit)? = null

    /** Invoked when a chat is deleted for everyone. */
    var onChatDeleted: ((Int) -> Unit)? = null

    private fun wsUrl(): String =
        "ws://$host:$port/app/$key?protocol=7&client=desktop&version=1.0&flash=false"

    fun connect() {
        closed = false
        val request = Request.Builder().url(wsUrl()).build()
        socket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onMessage(webSocket: WebSocket, text: String) = handle(text)
            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) = scheduleReconnect()
            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) = scheduleReconnect()
        })
    }

    fun subscribe(channel: String) {
        pending.add(channel)
        socketId?.let { authorizeAndSubscribe(channel, it) }
    }

    fun close() {
        closed = true
        reconnectJob?.cancel()
        pending.clear()
        subscribed.clear()
        runCatching { socket?.close(1000, "bye") }
        socket = null
    }

    private fun scheduleReconnect() {
        if (closed) return
        socketId = null
        subscribed.clear()
        reconnectJob?.cancel()
        reconnectJob = scope.launch {
            delay(3000)
            if (!closed) connect()
        }
    }

    private fun send(json: JSONObject) {
        runCatching { socket?.send(json.toString()) }
    }

    private fun handle(text: String) {
        val obj = runCatching { JSONObject(text) }.getOrNull() ?: return
        when (val event = obj.optString("event")) {
            "pusher:connection_established" -> {
                val data = runCatching { JSONObject(obj.optString("data")) }.getOrNull()
                socketId = data?.optString("socket_id")
                val sid = socketId ?: return
                pending.toList().forEach { authorizeAndSubscribe(it, sid) }
            }
            "pusher:ping" -> send(JSONObject().put("event", "pusher:pong").put("data", JSONObject()))
            "pusher_internal:subscription_succeeded" -> {
                obj.optString("channel").takeIf { it.isNotEmpty() }?.let { subscribed.add(it) }
            }
            else -> {
                if (event.endsWith("ChatDeleted")) {
                    val data = runCatching { JSONObject(obj.optString("data")) }.getOrNull()
                    val chatId = data?.optInt("chat_id", -1) ?: -1
                    if (chatId > 0) onChatDeleted?.invoke(chatId)
                }
                if (event.endsWith("MessageCreated") ||
                    event.endsWith("MessageUpdated") ||
                    event.endsWith("MessageDeleted")
                ) {
                    onMessageEvent?.invoke(obj)
                }
            }
        }
    }

    private fun authorizeAndSubscribe(channel: String, socketId: String) {
        if (subscribed.contains(channel)) return
        scope.launch {
            try {
                val token = tokenProvider() ?: return@launch
                val form = FormBody.Builder()
                    .add("socket_id", socketId)
                    .add("channel_name", channel)
                    .build()
                val request = Request.Builder()
                    .url(authUrl)
                    .post(form)
                    .header("Authorization", "Bearer $token")
                    .header("Accept", "application/json")
                    .build()

                client.newCall(request).execute().use { response ->
                    if (!response.isSuccessful) return@use
                    val body = response.body?.string() ?: return@use
                    val auth = runCatching { JSONObject(body).optString("auth") }.getOrDefault("")
                    if (auth.isBlank()) return@use
                    val data = JSONObject().put("channel", channel).put("auth", auth)
                    send(JSONObject().put("event", "pusher:subscribe").put("data", data))
                }
            } catch (_: Throwable) {
            }
        }
    }
}

// ───────────────────────────── ntfy subscription ─────────────────────────────

private class NtfyClient(
    private val baseUrl: String,
    private val topic: String,
    private val user: String? = null,
    private val pass: String? = null,
    private val onMessage: (JSONObject) -> Unit,
) {
    private val client = OkHttpClient.Builder()
        .readTimeout(0, TimeUnit.MILLISECONDS)
        .build()

    @Volatile
    private var closed = false

    private var thread: Thread? = null

    fun connect() {
        if (baseUrl.isBlank() || topic.isBlank()) return
        closed = false
        thread = Thread {
            try {
                val builder = Request.Builder().url("$baseUrl/$topic/json")
                val u = user
                val p = pass
                if (!u.isNullOrBlank() && p != null) {
                    val creds = Base64.getEncoder().encodeToString("$u:$p".toByteArray())
                    builder.header("Authorization", "Basic $creds")
                }
                val request = builder.build()
                client.newCall(request).execute().use { response ->
                    val source = response.body?.source() ?: return@use
                    while (!closed && !source.exhausted()) {
                        val line = source.readUtf8Line() ?: break
                        if (line.isBlank()) continue
                        val obj = runCatching { JSONObject(line) }.getOrNull() ?: continue
                        if (obj.optString("event") == "message") onMessage(obj)
                    }
                }
            } catch (_: Throwable) {
            }
        }.apply {
            isDaemon = true
            start()
        }
    }

    fun close() {
        closed = true
        thread?.interrupt()
        thread = null
    }
}

// ───────────────────────────── Realtime controller ─────────────────────────────

private object RealtimeController {
    val tick = MutableStateFlow(0)

    /** Chat id that was just deleted while it was open in the UI. */
    val deletedChat = MutableStateFlow<Int?>(null)

    private var ws: RealtimeClient? = null
    private var ntfy: NtfyClient? = null

    @Volatile
    private var activeId: String? = null

    /** Chat currently open in the UI; notifications are suppressed for it. */
    @Volatile
    var activeChatId: Int? = null

    @Synchronized
    fun start(account: Account) {
        if (activeId == account.id && ws != null) return
        stop()
        activeId = account.id

        val token = account.token
        ws = RealtimeClient(
            host = account.host,
            port = account.port,
            key = "chat-key",
            authUrl = "http://${account.host}:${account.port}/broadcasting/auth",
            tokenProvider = { token },
        ).also { rt ->
            rt.onMessageEvent = { event ->
                val data = runCatching { JSONObject(event.optString("data")) }.getOrNull()
                val senderId = data?.optInt("sender_id", -1) ?: -1
                val chatId = data?.optInt("chat_id", -1) ?: -1

                // Skip own messages and the chat that is currently open.
                if (senderId != account.user.id && chatId != activeChatId) {
                    Notifier.notify("Чат", previewOf(event))
                    tick.value++
                }
            }
            rt.onChatDeleted = { id ->
                if (id == activeChatId) deletedChat.value = id
                tick.value++
            }
            rt.connect()
            rt.subscribe("private-user.${account.user.id}")
        }

        ntfy = NtfyClient(
            baseUrl = "http://${account.effectiveNtfyHost}:${account.effectiveNtfyPort}",
            topic = account.topic,
            user = account.effectiveNtfyUser,
            pass = account.effectiveNtfyPass,
        ) { obj ->
            Notifier.notify(
                obj.optString("title").ifBlank { "Чат" },
                obj.optString("message").ifBlank { "Новое уведомление" },
            )
            tick.value++
        }.also { it.connect() }
    }

    @Synchronized
    fun stop() {
        ws?.close()
        ws = null
        ntfy?.close()
        ntfy = null
        activeId = null
    }

    private fun previewOf(event: JSONObject): String {
        val data = runCatching { JSONObject(event.optString("data")) }.getOrNull()
        return data?.optString("body_preview")?.takeIf { it.isNotBlank() }
            ?: data?.optString("body")?.takeIf { it.isNotBlank() }
            ?: "Новое сообщение"
    }
}

// ───────────────────────────── Settings & autostart ─────────────────────────────

@Composable
private fun SettingsDialog(onClose: () -> Unit) {
    var autostart by remember { mutableStateOf(Settings.autostart) }
    var startInTray by remember { mutableStateOf(Settings.startInTray) }

    AlertDialog(
        onDismissRequest = onClose,
        title = { Text("Настройки") },
        text = {
            Column {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.fillMaxWidth().clickable(enabled = Autostart.supported) {
                        val v = !autostart
                        autostart = v
                        Settings.setAutostart(v)
                    },
                ) {
                    Checkbox(
                        checked = autostart,
                        onCheckedChange = { v ->
                            autostart = v
                            Settings.setAutostart(v)
                        },
                        enabled = Autostart.supported,
                    )
                    Spacer(Modifier.width(8.dp))
                    Column {
                        Text("Автозапуск при входе в Windows")
                        if (!Autostart.supported) {
                            Text("Доступно в установленной версии", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }
                Spacer(Modifier.heightIn(min = 8.dp))
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.fillMaxWidth().clickable {
                        val v = !startInTray
                        startInTray = v
                        Settings.setStartInTray(v)
                    },
                ) {
                    Checkbox(
                        checked = startInTray,
                        onCheckedChange = { v ->
                            startInTray = v
                            Settings.setStartInTray(v)
                        },
                    )
                    Spacer(Modifier.width(8.dp))
                    Text("Сворачивать в трей при запуске")
                }
                Spacer(Modifier.heightIn(min = 8.dp))
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.fillMaxWidth().clickable {
                        Settings.setDarkTheme(!Settings.darkThemeState.value)
                    },
                ) {
                    Checkbox(
                        checked = Settings.darkThemeState.value,
                        onCheckedChange = { v -> Settings.setDarkTheme(v) },
                    )
                    Spacer(Modifier.width(8.dp))
                    Text("Тёмная тема")
                }
            }
        },
        confirmButton = { TextButton(onClick = onClose) { Text("Готово") } },
    )
}

private object Settings {
    private val prefs: Preferences = Preferences.userRoot().node("tasksbase_chat_desktop")

    var autostart: Boolean = false
        private set
    var startInTray: Boolean = false
        private set

    val darkThemeState = mutableStateOf(false)

    fun load() {
        autostart = prefs.getBoolean("autostart", false)
        startInTray = prefs.getBoolean("start_in_tray", false)
        darkThemeState.value = prefs.getBoolean("dark_theme", false)
    }

    fun setAutostart(enabled: Boolean) {
        autostart = enabled
        prefs.putBoolean("autostart", enabled)
        Autostart.apply(enabled)
    }

    fun setStartInTray(enabled: Boolean) {
        startInTray = enabled
        prefs.putBoolean("start_in_tray", enabled)
    }

    fun setDarkTheme(enabled: Boolean) {
        darkThemeState.value = enabled
        prefs.putBoolean("dark_theme", enabled)
    }
}

/** Windows autostart via the HKCU Run registry key (no admin required). */
private object Autostart {
    private const val RUN_KEY = "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run"
    private const val VALUE_NAME = "TasksbaseChat"

    val supported: Boolean
        get() = System.getProperty("os.name").orEmpty().lowercase().contains("win")

    private fun executablePath(): String? {
        System.getProperty("jpackage.app-path")?.takeIf { it.isNotBlank() }?.let { return it }
        val cmd = ProcessHandle.current().info().command().orElse(null) ?: return null
        val name = cmd.substringAfterLast('\\').lowercase()
        return if (cmd.lowercase().endsWith(".exe") && name != "java.exe" && name != "javaw.exe") cmd else null
    }

    fun apply(enabled: Boolean) {
        if (!supported) return
        val exe = executablePath() ?: return
        runCatching {
            if (enabled) {
                ProcessBuilder(
                    "reg", "add", RUN_KEY, "/v", VALUE_NAME,
                    "/t", "REG_SZ", "/d", "\"$exe\"", "/f",
                ).start().waitFor()
            } else {
                ProcessBuilder("reg", "delete", RUN_KEY, "/v", VALUE_NAME, "/f").start().waitFor()
            }
        }
    }
}

// ───────────────────────────── Updates ─────────────────────────────

private const val APP_VERSION = "1.0.1"

private data class DesktopUpdate(
    val version: String = "",
    val path: String = "",
    val notes: String? = null,
)

private object Updater {
    private val gson = Gson()

    private val client = OkHttpClient.Builder()
        .connectTimeout(10, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    fun check(): DesktopUpdate? = runCatching {
        val url = "http://${Net.host}:${Net.port}/updates/windows/latest.json"
        val request = Request.Builder().url(url).build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) return@use null
            val body = response.body?.string() ?: return@use null
            val info = gson.fromJson(body, DesktopUpdate::class.java)
            if (info != null && isNewer(info.version)) info else null
        }
    }.getOrNull()

    fun download(info: DesktopUpdate): File {
        val url = "http://${Net.host}:${Net.port}${info.path}"
        val request = Request.Builder().url(url).build()
        val dir = File(System.getProperty("java.io.tmpdir"), "tasksbase-update").apply { mkdirs() }
        val name = info.path.substringAfterLast('/').ifBlank { "update.msi" }
        val target = File(dir, name)

        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) error("HTTP ${response.code}")
            val body = response.body ?: error("Пустой ответ")
            body.byteStream().use { input ->
                target.outputStream().use { output -> input.copyTo(output) }
            }
        }

        return target
    }

    fun install(file: File) {
        ProcessBuilder("msiexec", "/i", file.absolutePath).start()
    }

    private fun isNewer(remote: String): Boolean {
        if (remote.isBlank()) return false
        val current = APP_VERSION.split('.').map { it.toIntOrNull() ?: 0 }
        val candidate = remote.split('.').map { it.toIntOrNull() ?: 0 }
        for (i in 0 until maxOf(current.size, candidate.size)) {
            val a = current.getOrElse(i) { 0 }
            val b = candidate.getOrElse(i) { 0 }
            if (b > a) return true
            if (b < a) return false
        }
        return false
    }
}
