package com.tasksbase.chat.data.remote

import okhttp3.MultipartBody
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Part
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

    @Multipart
    @POST("files/upload")
    suspend fun uploadFile(@Part file: MultipartBody.Part): FileDto

    @POST("chat/{id}/read")
    suspend fun markRead(@Path("id") chatId: Int)

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

    @POST("chat/{id}/members/remove")
    suspend fun removeMember(@Path("id") chatId: Int, @Body body: RemoveMemberRequest): ChatDto

    @POST("chat/{id}/leave")
    suspend fun leaveChat(@Path("id") chatId: Int)

    @DELETE("chat/{id}")
    suspend fun deleteChat(@Path("id") chatId: Int)
}
