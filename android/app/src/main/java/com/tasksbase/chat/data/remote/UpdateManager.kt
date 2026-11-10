package com.tasksbase.chat.data.remote

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import com.google.gson.annotations.SerializedName
import com.tasksbase.chat.BuildConfig
import com.tasksbase.chat.di.ServiceLocator
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.Request
import java.io.File

data class UpdateInfo(
    val version: String = "",
    @SerializedName("versionCode") val versionCode: Int = 0,
    val path: String = "",
    val notes: String? = null,
)

/**
 * Checks the update server (served by nginx at /updates/), downloads a newer
 * APK and launches the system installer.
 */
object UpdateManager {

    suspend fun check(): UpdateInfo? = withContext(Dispatchers.IO) {
        runCatching {
            val base = "http://${ServiceLocator.server.host}:${ServiceLocator.server.port}"
            val request = Request.Builder()
                .url("$base/updates/android/latest.json")
                .build()

            ServiceLocator.okHttp.newCall(request).execute().use { response ->
                if (!response.isSuccessful) return@use null
                val body = response.body?.string() ?: return@use null
                val info = ServiceLocator.gson.fromJson(body, UpdateInfo::class.java)
                if (info != null && info.versionCode > BuildConfig.VERSION_CODE) info else null
            }
        }.getOrNull()
    }

    suspend fun downloadAndInstall(context: Context, info: UpdateInfo): Result<Unit> =
        withContext(Dispatchers.IO) {
            runCatching {
                val base = "http://${ServiceLocator.server.host}:${ServiceLocator.server.port}"
                val request = Request.Builder()
                    .url(base + info.path)
                    .build()

                val dir = File(context.cacheDir, "updates").apply { mkdirs() }
                val target = File(dir, "app-update.apk")

                ServiceLocator.okHttp.newCall(request).execute().use { response ->
                    if (!response.isSuccessful) error("HTTP ${response.code}")
                    val body = response.body ?: error("Пустой ответ")
                    body.byteStream().use { input ->
                        target.outputStream().use { output -> input.copyTo(output) }
                    }
                }

                install(context, target)
                Unit
            }
        }

    private fun install(context: Context, apk: File) {
        val uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            apk,
        )
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, "application/vnd.android.package-archive")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(intent)
    }
}
