package com.tasksbase.chat.data.repository

import android.content.ContentResolver
import android.net.Uri
import com.tasksbase.chat.data.remote.AddMembersRequest
import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.CreateChatRequest
import com.tasksbase.chat.data.remote.FileDto
import com.tasksbase.chat.data.remote.MessageDto
import com.tasksbase.chat.data.remote.RemoveMemberRequest
import com.tasksbase.chat.data.remote.SendMessageRequest
import com.tasksbase.chat.data.remote.UpdateChatRequest
import com.tasksbase.chat.data.remote.UserDto
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody

class ChatRepository(private val api: ApiService) {

    suspend fun myChats(): List<ChatDto> = api.myChats().data

    /** Messages come newest-first from the API; return oldest-first for display. */
    suspend fun messages(chatId: Int): List<MessageDto> = api.messages(chatId).data.asReversed()

    suspend fun sendMessage(chatId: Int, text: String): MessageDto =
        api.sendMessage(chatId, SendMessageRequest(text))

    suspend fun markRead(chatId: Int) {
        runCatching { api.markRead(chatId) }
    }

    suspend fun users(): List<UserDto> = api.users()

    suspend fun createChat(
        type: String,
        name: String?,
        description: String?,
        userIds: List<Int>,
    ): ChatDto = api.createChat(CreateChatRequest(type, name, description, userIds))

    suspend fun chat(chatId: Int): ChatDto = api.chat(chatId)

    suspend fun renameChat(chatId: Int, name: String): ChatDto =
        api.updateChat(chatId, UpdateChatRequest(name = name))

    suspend fun addMembers(chatId: Int, userIds: List<Int>): ChatDto =
        api.addMembers(chatId, AddMembersRequest(userIds))

    suspend fun removeMember(chatId: Int, userId: Int): ChatDto =
        api.removeMember(chatId, RemoveMemberRequest(userId))

    suspend fun leaveChat(chatId: Int) = api.leaveChat(chatId)

    suspend fun deleteChat(chatId: Int) = api.deleteChat(chatId)

    /** Uploads a picked file/image (GIF is an image), then sends it as a message. */
    suspend fun sendAttachment(chatId: Int, uri: Uri, contentResolver: ContentResolver): MessageDto {
        val mime = contentResolver.getType(uri) ?: "application/octet-stream"
        val bytes = contentResolver.openInputStream(uri)?.use { it.readBytes() }
            ?: throw IllegalStateException("Не удалось прочитать файл")

        val part = MultipartBody.Part.createFormData(
            "file",
            "upload_${System.currentTimeMillis()}",
            bytes.toRequestBody(mime.toMediaTypeOrNull()),
        )

        val uploaded: FileDto = api.uploadFile(part)
        val type = if (mime.startsWith("image/")) "image" else "file"

        return api.sendMessage(
            chatId,
            SendMessageRequest(body = null, type = type, fileIds = listOf(uploaded.id)),
        )
    }
}
