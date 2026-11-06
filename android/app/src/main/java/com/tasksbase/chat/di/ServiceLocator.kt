package com.tasksbase.chat.di

import android.content.Context
import com.google.gson.Gson
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.data.local.Account
import com.tasksbase.chat.data.local.AccountStore
import com.tasksbase.chat.data.local.ServerConfig
import com.tasksbase.chat.data.local.SettingsStore
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

    /** Emits true once the persisted accounts have been loaded. */
    val sessionReady = MutableStateFlow(false)

    /** Emits the currently active account (used to re-key the UI on switch). */
    val activeAccountFlow = MutableStateFlow<Account?>(null)

    /** Dark theme flag (persisted in SettingsStore). */
    val darkThemeFlow = MutableStateFlow(false)

    val gson: Gson by lazy { Gson() }

    val accountStore: AccountStore by lazy { AccountStore(appContext) }

    val settingsStore: SettingsStore by lazy { SettingsStore(appContext) }

    @Volatile
    var accounts: List<Account> = emptyList()
        private set

    @Volatile
    var activeAccount: Account? = null
        private set

    /** Server used by the API/WS clients — always mirrors the active account. */
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
            // ServerAddressInterceptor from the active account.
            .baseUrl("http://localhost/api/v1/")
            .client(okHttp)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ApiService::class.java)
    }

    val authRepository: AuthRepository by lazy { AuthRepository(api) }
    val chatRepository: ChatRepository by lazy { ChatRepository(api) }

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    /** Loads persisted accounts and activates the last-used one. */
    suspend fun loadAccounts() {
        val (list, activeId) = accountStore.loadAll()
        accounts = list
        val active = list.firstOrNull { it.id == activeId } ?: list.firstOrNull()
        applyActive(active)
    }

    /** Loads persisted UI settings (theme). */
    suspend fun loadSettings() {
        darkThemeFlow.value = settingsStore.loadDarkTheme()
    }

    suspend fun updateDarkTheme(enabled: Boolean) {
        darkThemeFlow.value = enabled
        settingsStore.saveDarkTheme(enabled)
    }

    private fun applyActive(account: Account?) {
        activeAccount = account
        activeAccountFlow.value = account
        server = account?.serverConfig() ?: ServerConfig.default()
        Session.activeAccount = account
        Session.token = account?.token
        Session.currentUser = account?.user
    }

    /** Temporarily point the API at a server so a login can be performed. */
    fun setLoginServer(config: ServerConfig) {
        server = config
    }

    suspend fun addOrUpdateAccount(account: Account) {
        val newList = accounts.filter { it.id != account.id } + account
        accounts = newList
        applyActive(account)
        accountStore.saveAll(newList, account.id)
    }

    suspend fun setActive(accountId: String) {
        val acc = accounts.firstOrNull { it.id == accountId } ?: return
        applyActive(acc)
        accountStore.saveAll(accounts, acc.id)
    }

    suspend fun removeActiveAccount() {
        val active = activeAccount ?: return
        val newList = accounts.filter { it.id != active.id }
        accounts = newList
        val next = newList.firstOrNull()
        applyActive(next)
        accountStore.saveAll(newList, next?.id)
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
