package com.tasksbase.chat.data.remote

import okhttp3.Interceptor
import okhttp3.Response

/** Adds the Sanctum bearer token to every outgoing request (if available). */
class AuthInterceptor(private val tokenProvider: () -> String?) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val token = tokenProvider()
        val original = chain.request()
        val request = if (token.isNullOrBlank() || original.header("Authorization") != null) {
            original
        } else {
            original.newBuilder()
                .header("Authorization", "Bearer $token")
                .build()
        }
        return chain.proceed(request)
    }
}
