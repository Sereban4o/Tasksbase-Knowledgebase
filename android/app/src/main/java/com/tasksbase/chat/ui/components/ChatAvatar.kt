package com.tasksbase.chat.ui.components

import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session

/** Resolves an avatar value to an absolute URL (if it looks like one). */
fun resolveAvatarUrl(avatar: String?): String? {
    val value = avatar?.trim().orEmpty()
    if (value.isEmpty()) return null
    val server = ServiceLocator.server
    return when {
        value.startsWith("http://", true) || value.startsWith("https://", true) -> value
        value.startsWith("/") -> "http://${server.host}:${server.port}$value"
        else -> null
    }
}

/** Avatar URL for a chat: the other user's avatar for DMs, else the chat avatar. */
fun chatAvatarUrl(chat: ChatDto, myId: Int? = Session.currentUser?.id): String? =
    if (chat.type == "dm") chat.members?.firstOrNull { it.id != myId }?.avatar else chat.avatar

@Composable
fun ChatAvatar(
    avatarUrl: String?,
    fallbackText: String,
    color: Color,
    size: Dp = 48.dp,
    modifier: Modifier = Modifier,
) {
    val url = resolveAvatarUrl(avatarUrl)
    if (url != null) {
        AsyncImage(
            model = url,
            contentDescription = null,
            contentScale = ContentScale.Crop,
            modifier = modifier.size(size).clip(CircleShape),
        )
    } else {
        LetterAvatar(text = fallbackText, color = color, size = size, modifier = modifier)
    }
}
