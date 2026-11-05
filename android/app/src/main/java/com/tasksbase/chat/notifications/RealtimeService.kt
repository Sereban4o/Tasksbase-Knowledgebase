package com.tasksbase.chat.notifications

import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.IBinder
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.di.ServiceLocator
import com.tasksbase.chat.di.Session
import com.tasksbase.chat.realtime.RealtimeClient
import org.json.JSONObject

/**
 * Keeps a Reverb (Pusher protocol) connection alive for the logged-in user and
 * posts local notifications for messages delivered to the personal
 * `private-user.{id}` channel (DMs, group and channel messages where the user
 * is a member).
 */
class RealtimeService : Service() {

    private var client: RealtimeClient? = null
    private var ntfy: NtfyClient? = null

    override fun onCreate() {
        super.onCreate()
        NotificationHelper.ensureChannels(this)
        startForeground(
            NotificationHelper.SERVICE_NOTIFICATION_ID,
            NotificationHelper.buildServiceNotification(this),
        )
        connect()
    }

    private fun connect() {
        val user = Session.currentUser ?: return
        if (Session.token.isNullOrBlank()) return

        val config = ServiceLocator.server
        val realtime = RealtimeClient(
            host = config.wsHost,
            port = config.wsPort,
            key = BuildConfig.WS_KEY,
            authUrl = config.broadcastAuthUrl,
            tokenProvider = { Session.token },
        )
        realtime.onUserMessage = { data -> handleMessage(data) }
        realtime.connect()
        realtime.subscribe("private-user.${user.id}")
        client = realtime

        // Per-account ntfy subscription (each account may use a different server).
        val account = ServiceLocator.activeAccount
        ntfy?.close()
        ntfy = null
        if (account != null && account.ntfyHost.isNotBlank()) {
            ntfy = NtfyClient(account.ntfyBaseUrl, account.ntfyTopic) { obj ->
                val title = obj.optString("title").ifBlank { "Tasksbase" }
                val text = obj.optString("message").ifBlank { "Новое уведомление" }
                NotificationHelper.showMessage(this, title, text)
            }.also { it.connect() }
        }
    }

    private fun handleMessage(data: JSONObject) {
        val senderId = data.optInt("sender_id", -1)
        if (senderId == Session.currentUser?.id) return

        val chatId = data.optInt("chat_id", -1)
        if (chatId == Session.activeChatId) return

        val title = data.optString("chat_name").ifBlank { data.optString("sender_name") }
        val text = data.optString("body_preview").ifBlank { "Новое сообщение" }

        NotificationHelper.showMessage(this, title, text)
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (client == null) connect()
        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        client?.close()
        client = null
        ntfy?.close()
        ntfy = null
        super.onDestroy()
    }

    companion object {
        fun start(context: Context) {
            val intent = Intent(context, RealtimeService::class.java)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(intent)
            } else {
                context.startService(intent)
            }
        }

        fun stop(context: Context) {
            context.stopService(Intent(context, RealtimeService::class.java))
        }
    }
}
