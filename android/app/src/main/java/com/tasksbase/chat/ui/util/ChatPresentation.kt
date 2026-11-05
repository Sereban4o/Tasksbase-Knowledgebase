package com.tasksbase.chat.ui.util

import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.MessageDto
import com.tasksbase.chat.di.Session

fun chatDisplayName(chat: ChatDto, myId: Int? = Session.currentUser?.id): String {
    chat.name?.takeIf { it.isNotBlank() }?.let { return it }
    if (chat.type == "dm") {
        val other = chat.members?.firstOrNull { it.id != myId }
        return other?.fullName ?: "Личный чат"
    }
    return if (chat.type == "channel") "Канал" else "Чат"
}

fun chatAvatarText(chat: ChatDto, myId: Int? = Session.currentUser?.id): String {
    chat.name?.takeIf { it.isNotBlank() }?.let { return it.first().uppercase() }
    if (chat.type == "dm") {
        val other = chat.members?.firstOrNull { it.id != myId }
        if (other != null) return initialsOf(other.firstName, other.lastName, "?")
    }
    return if (chat.type == "channel") "#" else "@"
}

fun chatAvatarOwnerId(chat: ChatDto, myId: Int? = Session.currentUser?.id): Int? {
    if (chat.type == "dm") return chat.members?.firstOrNull { it.id != myId }?.id
    return chat.id
}

fun chatSubtitle(chat: ChatDto): String = when (chat.type) {
    "group" -> "${chat.members?.size ?: 0} участников"
    "channel" -> "канал"
    else -> "личный чат"
}

fun chatPreview(chat: ChatDto): String {
    val body = chat.lastMessage?.body
    return if (body.isNullOrBlank()) "Нет сообщений" else body
}

fun messageAuthorName(message: MessageDto): String =
    message.sender?.fullName ?: "Пользователь"
