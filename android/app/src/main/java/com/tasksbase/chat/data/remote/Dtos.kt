package com.tasksbase.chat.data.remote

import com.google.gson.annotations.SerializedName

data class UserDto(
    val id: Int = 0,
    val email: String? = null,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    val avatar: String? = null,
) {
    val fullName: String
        get() = listOfNotNull(firstName, lastName)
            .joinToString(" ")
            .trim()
            .ifBlank { email ?: "Пользователь" }

    val initials: String
        get() {
            val first = firstName?.firstOrNull()?.toString() ?: ""
            val last = lastName?.firstOrNull()?.toString() ?: ""
            return (first + last).uppercase().ifBlank { "?" }
        }
}

data class MessageFileDto(
    val id: Int = 0,
    @SerializedName("mime_type") val mimeType: String? = null,
    @SerializedName("thumbnail_url") val thumbnailUrl: String? = null,
    val url: String? = null,
    @SerializedName("file_name") val fileName: String? = null,
)

data class MessageDto(
    val id: Int = 0,
    @SerializedName("chat_id") val chatId: Int = 0,
    @SerializedName("sender_id") val senderId: Int = 0,
    @SerializedName("parent_id") val parentId: Int? = null,
    val body: String? = null,
    val type: String = "text",
    @SerializedName("created_at") val createdAt: String? = null,
    @SerializedName("edited_at") val editedAt: String? = null,
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
    @SerializedName("last_message_at") val lastMessageAt: String? = null,
    @SerializedName("created_at") val createdAt: String? = null,
    @SerializedName("messages_count") val messagesCount: Int = 0,
    @SerializedName("last_message") val lastMessage: MessageDto? = null,
    val members: List<UserDto>? = null,
)

data class PageDto<T>(
    val data: List<T> = emptyList(),
)

data class LoginRequest(
    val email: String,
    val password: String,
)

data class LoginResponse(
    val token: String,
    val user: UserDto,
)

data class CreateChatRequest(
    val type: String,
    val name: String?,
    val description: String?,
    @SerializedName("user_ids") val userIds: List<Int>,
)

data class SendMessageRequest(
    val body: String?,
    val type: String = "text",
)
