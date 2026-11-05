package com.tasksbase.chat.data.repository

import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.ChatDto
import com.tasksbase.chat.data.remote.CreateChatRequest
import com.tasksbase.chat.data.remote.MessageDto
import com.tasksbase.chat.data.remote.SendMessageRequest
import com.tasksbase.chat.data.remote.UserDto

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
}
