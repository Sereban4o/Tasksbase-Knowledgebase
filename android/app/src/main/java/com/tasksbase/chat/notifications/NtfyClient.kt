package com.tasksbase.chat.notifications

import android.util.Base64
import okhttp3.OkHttpClient
import okhttp3.Request
import org.json.JSONObject
import java.util.concurrent.TimeUnit

/**
 * Subscribes to a self-hosted ntfy topic using the JSON streaming endpoint
 * (`GET {baseUrl}/{topic}/json`) and invokes [onMessage] for each message.
 *
 * Each account carries its own ntfy base URL, so multiple accounts connected to
 * different servers receive push from different ntfy instances.
 */
class NtfyClient(
    private val baseUrl: String,
    private val topic: String,
    private val user: String? = null,
    private val pass: String? = null,
    private val onMessage: (JSONObject) -> Unit,
) {
    private val client = OkHttpClient.Builder()
        .readTimeout(0, TimeUnit.MILLISECONDS)
        .build()

    @Volatile
    private var closed = false

    private var thread: Thread? = null

    fun connect() {
        if (baseUrl.isBlank() || topic.isBlank()) return
        closed = false
        thread = Thread {
            try {
                val builder = Request.Builder().url("$baseUrl/$topic/json")
                val u = user
                val p = pass
                if (!u.isNullOrBlank() && p != null) {
                    val creds = Base64.encodeToString("$u:$p".toByteArray(), Base64.NO_WRAP)
                    builder.header("Authorization", "Basic $creds")
                }
                val request = builder.build()
                client.newCall(request).execute().use { response ->
                    val source = response.body?.source() ?: return@use
                    while (!closed && !source.exhausted()) {
                        val line = source.readUtf8Line() ?: break
                        if (line.isBlank()) continue
                        val obj = runCatching { JSONObject(line) }.getOrNull() ?: continue
                        if (obj.optString("event") == "message") onMessage(obj)
                    }
                }
            } catch (_: Throwable) {
                // Best-effort: ignore disconnects.
            }
        }.apply {
            isDaemon = true
            start()
        }
    }

    fun close() {
        closed = true
        thread?.interrupt()
        thread = null
    }
}
