package com.tasksbase.chat.realtime

import android.util.Log
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import okhttp3.FormBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.Response
import okhttp3.WebSocket
import okhttp3.WebSocketListener
import org.json.JSONObject
import java.util.concurrent.TimeUnit

/**
 * Minimal Pusher-protocol client for Laravel Reverb.
 *
 * Connects to ws://host:port/app/{key}, authorizes private channels through
 * Laravel's /broadcasting/auth endpoint (Bearer token) and notifies a listener
 * for Message* events. Reconnects automatically.
 */
class RealtimeClient(
    private val host: String,
    private val port: Int,
    private val key: String,
    private val authUrl: String,
    private val tokenProvider: () -> String?,
) {
    private val client = OkHttpClient.Builder()
        .pingInterval(30, TimeUnit.SECONDS)
        .build()

    private val scope = CoroutineScope(Dispatchers.IO)
    private var reconnectJob: Job? = null

    private var socket: WebSocket? = null
    private var socketId: String? = null
    private var closed = false

    private val pendingChannels = mutableSetOf<String>()
    private val subscribedChannels = mutableSetOf<String>()

    /** Invoked for MessageCreated / MessageUpdated / MessageDeleted. */
    var onMessageEvent: (() -> Unit)? = null

    private fun wsUrl(): String =
        "ws://$host:$port/app/$key?protocol=7&client=android&version=1.0&flash=false"

    fun connect() {
        closed = false
        val request = Request.Builder().url(wsUrl()).build()
        socket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onMessage(webSocket: WebSocket, text: String) = handleMessage(text)

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                Log.w(TAG, "socket failure: ${t.message}")
                scheduleReconnect()
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                scheduleReconnect()
            }
        })
    }

    fun subscribe(channel: String) {
        pendingChannels.add(channel)
        val sid = socketId ?: return
        authorizeAndSubscribe(channel, sid)
    }

    fun unsubscribe(channel: String) {
        pendingChannels.remove(channel)
        subscribedChannels.remove(channel)
        send(JSONObject().put("event", "pusher:unsubscribe").put("data", JSONObject().put("channel", channel)))
    }

    fun close() {
        closed = true
        reconnectJob?.cancel()
        pendingChannels.clear()
        subscribedChannels.clear()
        runCatching { socket?.close(1000, "bye") }
        socket = null
    }

    private fun scheduleReconnect() {
        if (closed) return
        socketId = null
        subscribedChannels.clear()
        reconnectJob?.cancel()
        reconnectJob = scope.launch {
            delay(3000)
            if (!closed) connect()
        }
    }

    private fun send(json: JSONObject) {
        runCatching { socket?.send(json.toString()) }
    }

    private fun handleMessage(text: String) {
        val obj = runCatching { JSONObject(text) }.getOrNull() ?: return
        when (val event = obj.optString("event")) {
            "pusher:connection_established" -> {
                val data = runCatching { JSONObject(obj.optString("data")) }.getOrNull()
                socketId = data?.optString("socket_id")
                val sid = socketId ?: return
                pendingChannels.toList().forEach { authorizeAndSubscribe(it, sid) }
            }

            "pusher:ping" -> send(JSONObject().put("event", "pusher:pong").put("data", JSONObject()))

            "pusher_internal:subscription_succeeded" -> {
                obj.optString("channel").takeIf { it.isNotEmpty() }?.let { subscribedChannels.add(it) }
            }

            else -> {
                if (event.endsWith("MessageCreated") ||
                    event.endsWith("MessageUpdated") ||
                    event.endsWith("MessageDeleted")
                ) {
                    onMessageEvent?.invoke()
                }
            }
        }
    }

    private fun authorizeAndSubscribe(channel: String, socketId: String) {
        if (subscribedChannels.contains(channel)) return
        scope.launch {
            try {
                val token = tokenProvider() ?: return@launch
                val form = FormBody.Builder()
                    .add("socket_id", socketId)
                    .add("channel_name", channel)
                    .build()
                val request = Request.Builder()
                    .url(authUrl)
                    .post(form)
                    .header("Authorization", "Bearer $token")
                    .header("Accept", "application/json")
                    .build()

                val response = client.newCall(request).execute()
                response.use { resp ->
                    if (!resp.isSuccessful) {
                        Log.w(TAG, "auth for $channel failed: ${resp.code}")
                        return@use
                    }
                    val body = resp.body?.string() ?: return@use
                    val auth = runCatching { JSONObject(body).optString("auth") }.getOrDefault("")
                    if (auth.isBlank()) return@use
                    val data = JSONObject().put("channel", channel).put("auth", auth)
                    send(JSONObject().put("event", "pusher:subscribe").put("data", data))
                }
            } catch (e: Exception) {
                Log.w(TAG, "subscribe error: ${e.message}")
            }
        }
    }

    private companion object {
        const val TAG = "RealtimeClient"
    }
}
