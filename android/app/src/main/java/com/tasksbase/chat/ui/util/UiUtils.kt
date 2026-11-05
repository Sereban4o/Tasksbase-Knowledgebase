package com.tasksbase.chat.ui.util

import androidx.compose.ui.graphics.Color
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import java.util.TimeZone

private val AVATAR_COLORS = listOf(
    Color(0xFFFF6B6B), Color(0xFF4ECDC4), Color(0xFF45B7D1), Color(0xFF96CEB4),
    Color(0xFFFFEAA7), Color(0xFFDDA0DD), Color(0xFF98D8C8), Color(0xFFF7DC6F),
    Color(0xFFBB8FCE), Color(0xFF85C1E9),
)

fun avatarColorFor(id: Int?): Color {
    val value = id ?: 0
    return AVATAR_COLORS[((value % AVATAR_COLORS.size) + AVATAR_COLORS.size) % AVATAR_COLORS.size]
}

fun initialsOf(first: String?, last: String?, fallback: String = "?"): String {
    val a = first?.firstOrNull()?.toString() ?: ""
    val b = last?.firstOrNull()?.toString() ?: ""
    return (a + b).uppercase().ifBlank { fallback }
}

private fun parseIso(iso: String?): Date? {
    if (iso.isNullOrBlank()) return null
    val cleaned = iso.substringBefore('.').replace("Z", "")
    return try {
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
            .apply { timeZone = TimeZone.getTimeZone("UTC") }
            .parse(cleaned)
    } catch (e: Exception) {
        null
    }
}

fun formatMessageTime(iso: String?): String {
    val date = parseIso(iso) ?: return ""
    return try {
        SimpleDateFormat("HH:mm", Locale.getDefault()).format(date)
    } catch (e: Exception) {
        ""
    }
}

fun formatListTime(iso: String?): String {
    val date = parseIso(iso) ?: return ""
    val now = Calendar.getInstance()
    val then = Calendar.getInstance().apply { time = date }
    return try {
        if (now.get(Calendar.YEAR) == then.get(Calendar.YEAR) &&
            now.get(Calendar.DAY_OF_YEAR) == then.get(Calendar.DAY_OF_YEAR)
        ) {
            SimpleDateFormat("HH:mm", Locale.getDefault()).format(date)
        } else {
            SimpleDateFormat("dd.MM", Locale.getDefault()).format(date)
        }
    } catch (e: Exception) {
        ""
    }
}
