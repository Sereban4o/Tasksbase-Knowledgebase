package com.tasksbase.chat.data.remote

import com.tasksbase.chat.data.local.ServerConfig
import okhttp3.Interceptor
import okhttp3.Response

/**
 * Rewrites every outgoing request to the currently configured server address
 * (host + port). Retrofit is built with a neutral base URL, so the scheme,
 * host and port are taken from the runtime configuration instead of being
 * fixed at build time.
 */
class ServerAddressInterceptor(private val configProvider: () -> ServerConfig) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val config = configProvider()
        val original = chain.request()

        val newUrl = original.url.newBuilder()
            .scheme("http")
            .host(config.host)
            .port(config.port)
            .build()

        val request = original.newBuilder().url(newUrl).build()
        return chain.proceed(request)
    }
}
