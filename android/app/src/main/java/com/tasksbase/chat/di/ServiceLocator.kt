package com.tasksbase.chat.di

import android.content.Context
import com.google.gson.Gson
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.data.local.ServerConfig
import com.tasksbase.chat.data.local.ServerConfigStore
import com.tasksbase.chat.data.local.TokenStore
import com.tasksbase.chat.data.remote.ApiService
import com.tasksbase.chat.data.remote.AuthInterceptor
import com.tasksbase.chat.data.remote.ServerAddressInterceptor
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

    val serverConfigStore: ServerConfigStore by lazy { ServerConfigStore(appContext) }

    /** Current server address, kept in memory for fast access. */
    @Volatile
    var server: ServerConfig = ServerConfig.default()
        private set

    val okHttp: OkHttpClient by lazy {
        OkHttpClient.Builder()
            .addInterceptor(ServerAddressInterceptor { server })
            .addInterceptor(AuthInterceptor { Session.token })
            .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    val api: ApiService by lazy {
        Retrofit.Builder()
            // Neutral base URL: the real scheme/host/port are applied by
            // ServerAddressInterceptor from the runtime configuration.
            .baseUrl("http://localhost/api/v1/")
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

    /** Loads the persisted server address into memory (call once at startup). */
    suspend fun loadServerConfig() {
        server = serverConfigStore.load()
    }

    /**
     * Persists a new server address and drops the current session, because a
     * token issued by one server is not valid on another one.
     */
    suspend fun saveServer(host: String, port: Int) {
        val config = ServerConfig(host, port)
        serverConfigStore.save(config)
        server = config
        runCatching { tokenStore.clear() }
        Session.reset()
    }

    fun newRealtime(): RealtimeClient {
        val config = server
        return RealtimeClient(
            host = config.wsHost,
            port = config.wsPort,
            key = BuildConfig.WS_KEY,
            authUrl = config.broadcastAuthUrl,
            tokenProvider = { Session.token },
        )
    }
}
