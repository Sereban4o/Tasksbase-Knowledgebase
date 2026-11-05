package com.tasksbase.chat.data.remote

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface ApiService {

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @GET("chat/my")
    suspend fun myChats(@Query("per_page") perPage: Int = 50): PageDto<ChatDto>

    @GET("chat/{id}/messages")
    suspend fun messages(
        @Path("id") chatId: Int,
        @Query("per_page") perPage: Int = 50,
    ): PageDto<MessageDto>

    @POST("chat/{id}/messages")
    suspend fun sendMessage(
        @Path("id") chatId: Int,
        @Body body: SendMessageRequest,
    ): MessageDto

    @POST("chat/{id}/read")
    suspend fun markRead(@Path("id") chatId: Int)

    @GET("users/all")
    suspend fun users(): List<UserDto>

    @POST("chat")
    suspend fun createChat(@Body body: CreateChatRequest): ChatDto
}
