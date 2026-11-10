package com.tasksbase.chat.ui.chat

import android.net.Uri
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AttachFile
import androidx.compose.material.icons.filled.EmojiEmotions
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.Send
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil.ImageLoader
import coil.compose.AsyncImage
import coil.decode.GifDecoder
import coil.decode.ImageDecoderDecoder
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.MessageDto
import com.tasksbase.chat.data.remote.UserDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session
import com.tasksbase.chat.ui.components.ChatAvatar
import com.tasksbase.chat.ui.components.chatAvatarUrl
import com.tasksbase.chat.ui.components.CenterError
import com.tasksbase.chat.ui.components.CenterProgress
import com.tasksbase.chat.ui.components.CenterText
import com.tasksbase.chat.ui.components.LetterAvatar
import com.tasksbase.chat.ui.theme.LocalChatPalette
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

private val EMOJI_SET = listOf(
    "😀", "😁", "😂", "🤣", "😊", "😍", "😎", "🤔",
    "😢", "😡", "👍", "👎", "👏", "🙌", "🙏", "🤝",
    "🔥", "🎉", "❤️", "💔", "✅", "❌", "🚀", "⭐",
    "💡", "📌", "👀", "💬", "😴", "😅", "🤯", "🥳",
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChatScreen(chat: ChatDto, onBack: () -> Unit) {
    val scope = rememberCoroutineScope()
    val myId = Session.currentUser?.id
    val context = LocalContext.current

    var messages by remember { mutableStateOf<List<MessageDto>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var draft by remember { mutableStateOf("") }
    var refreshKey by remember { mutableIntStateOf(0) }
    var attaching by remember { mutableStateOf(false) }
    var showEmoji by remember { mutableStateOf(false) }
    var showInfo by remember { mutableStateOf(false) }
    var users by remember { mutableStateOf<List<UserDto>>(emptyList()) }

    // Coil loader with GIF support (coil-gif)
    val imageLoader = remember {
        ImageLoader.Builder(context)
            .components {
                if (Build.VERSION.SDK_INT >= 28) add(ImageDecoderDecoder.Factory())
                else add(GifDecoder.Factory())
            }
            .build()
    }

    suspend fun refresh() {
        runCatching { ServiceLocator.chatRepository.messages(chat.id) }
            .onSuccess {
                messages = it
                error = null
            }
            .onFailure { if (messages.isEmpty()) error = friendlyError(it) }
        loading = false
    }

    fun sendAttachment(uri: Uri) {
        attaching = true
        scope.launch {
            runCatching {
                ServiceLocator.chatRepository.sendAttachment(chat.id, uri, context.contentResolver)
            }
                .onSuccess { refresh() }
                .onFailure { error = friendlyError(it) }
            attaching = false
        }
    }

    val pickImage = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let { sendAttachment(it) }
    }
    val pickFile = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        uri?.let { sendAttachment(it) }
    }

    // Initial load, read receipt and polling fallback.
    LaunchedEffect(chat.id) {
        ServiceLocator.chatRepository.markRead(chat.id)
        while (isActive) {
            refresh()
            delay(30_000)
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
        realtime.onChatDeleted = { deletedId ->
            if (deletedId == chat.id) {
                Session.bumpChatList()
                onBack()
            }
        }
        realtime.connect()
        realtime.subscribe("private-chat.${chat.id}")
        onDispose {
            Session.activeChatId = null
            realtime.close()
        }
    }

    LaunchedEffect(showInfo) {
        if (showInfo && users.isEmpty()) {
            runCatching { ServiceLocator.chatRepository.users() }.onSuccess { users = it }
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
                        ChatAvatar(
                            avatarUrl = chatAvatarUrl(chat, myId),
                            fallbackText = chatAvatarText(chat, myId),
                            color = avatarColorFor(chatAvatarOwnerId(chat, myId)),
                            size = 36.dp,
                            modifier = Modifier.clickable { showInfo = true },
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
                            Text(text = chatSubtitle(chat), fontSize = 12.sp, color = LocalChatPalette.current.secondary)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = LocalChatPalette.current.surface),
            )
        },
    ) { padding ->
        if (showInfo) {
            ChatInfoDialog(
                chat = chat,
                myId = myId,
                users = users,
                onDismiss = { showInfo = false },
                onChanged = {
                    showInfo = false
                    Session.bumpChatList()
                    onBack()
                },
            )
        }
        Column(
            modifier = Modifier
                .padding(padding)
                .fillMaxSize()
                .background(LocalChatPalette.current.chatBg),
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
                            MessageBubble(
                                message = message,
                                isOwn = message.senderId == myId,
                                imageLoader = imageLoader,
                            )
                        }
                    }
                }
            }

            Surface(color = LocalChatPalette.current.surface, shadowElevation = 6.dp) {
                Column {
                    if (showEmoji) {
                        EmojiPanel { emoji -> draft += emoji }
                    }
                    if (attaching) {
                        LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        IconButton(onClick = { pickImage.launch("image/*") }, enabled = !attaching) {
                            Icon(Icons.Default.Image, contentDescription = "Картинка/GIF")
                        }
                        IconButton(onClick = { pickFile.launch(arrayOf("*/*")) }, enabled = !attaching) {
                            Icon(Icons.Default.AttachFile, contentDescription = "Файл")
                        }
                        OutlinedTextField(
                            value = draft,
                            onValueChange = { draft = it },
                            modifier = Modifier.weight(1f),
                            placeholder = { Text("Сообщение") },
                            maxLines = 4,
                        )
                        IconButton(onClick = { showEmoji = !showEmoji }) {
                            Icon(Icons.Default.EmojiEmotions, contentDescription = "Эмодзи")
                        }
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
}

@Composable
private fun EmojiPanel(onPick: (String) -> Unit) {
    LazyVerticalGrid(
        columns = GridCells.Fixed(8),
        modifier = Modifier.fillMaxWidth().height(180.dp).background(LocalChatPalette.current.surface).padding(4.dp),
    ) {
        items(EMOJI_SET.size) { index ->
            TextButton(onClick = { onPick(EMOJI_SET[index]) }, modifier = Modifier.height(40.dp)) {
                Text(text = EMOJI_SET[index], fontSize = 20.sp)
            }
        }
    }
}

private fun fileUrl(fileId: Int): String {
    val base = BuildConfig.API_BASE_URL.trimEnd('/')
    return "$base/files/$fileId"
}

@Composable
private fun MessageBubble(message: MessageDto, isOwn: Boolean, imageLoader: ImageLoader) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = if (isOwn) Arrangement.End else Arrangement.Start,
    ) {
        Column(
            modifier = Modifier
                .widthIn(max = 290.dp)
                .clip(RoundedCornerShape(12.dp))
                .background(if (isOwn) LocalChatPalette.current.outgoing else LocalChatPalette.current.surface)
                .padding(horizontal = 10.dp, vertical = 7.dp),
        ) {
            message.files?.forEach { mf ->
                val fileId = if (mf.fileId != 0) mf.fileId else (mf.file?.id ?: 0)
                if (mf.isImage && fileId != 0) {
                    AsyncImage(
                        model = fileUrl(fileId),
                        imageLoader = imageLoader,
                        contentDescription = mf.displayName,
                        contentScale = ContentScale.Fit,
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 240.dp)
                            .clip(RoundedCornerShape(8.dp))
                            .padding(bottom = 4.dp),
                    )
                } else {
                    Text(
                        text = "📎 ${mf.displayName}",
                        color = TgAccent,
                        fontSize = 14.sp,
                        modifier = Modifier.padding(bottom = 4.dp),
                    )
                }
            }

            if (!message.body.isNullOrBlank()) {
                Text(text = message.body, color = LocalChatPalette.current.text, fontSize = 15.sp)
            }
            Text(
                text = formatMessageTime(message.createdAt),
                fontSize = 10.sp,
                color = LocalChatPalette.current.secondary,
                modifier = Modifier.align(Alignment.End),
            )
        }
    }
}
