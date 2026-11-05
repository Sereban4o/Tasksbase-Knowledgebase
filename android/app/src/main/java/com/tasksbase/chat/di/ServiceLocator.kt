package com.tasksbase.chat.di

import android.content.Context
import com.google.gson.Gson
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.data.local.TokenStore
import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.AuthInterceptor
import com.tasksbase.chat.data.repository.AuthRepository
import com.tasksbase.chat.data.repository.ChatRepository
import com.tasksbase.chat.realtime.RealtimeClient
import kotlinx.coroutines.flow.MutableStateFlow
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

/** Lightweight manual DI (no Hilt required). */
object ServiceLocator {

    lateinit var appContext: Context
        private set

    /** Emits true once the persisted session has been loaded. */
    val sessionReady = MutableStateFlow(false)

    val gson: Gson by lazy { Gson() }

    val tokenStore: TokenStore by lazy { TokenStore(appContext) }

    val okHttp: OkHttpClient by lazy {
        OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor { Session.token })
            .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    val api: ApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(okHttp)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ApiService::class.java)
    }

    val authRepository: AuthRepository by lazy { AuthRepository(api, tokenStore) }
    val chatRepository: ChatRepository by lazy { ChatRepository(api) }

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    fun newRealtime(): RealtimeClient = RealtimeClient(
        host = BuildConfig.WS_HOST,
        port = BuildConfig.WS_PORT,
        key = BuildConfig.WS_KEY,
        authUrl = BuildConfig.BROADCAST_AUTH_URL,
        tokenProvider = { Session.token },
    )
}
