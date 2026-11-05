package com.tasksbase.chat.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val TgAccent = Color(0xFF3390EC)
val TgAccentDark = Color(0xFF2B82D9)
val TgChatBg = Color(0xFFE6EBEE)
val TgOutgoing = Color(0xFFEEFFDE)
val TgText = Color(0xFF0F0F0F)
val TgSecondary = Color(0xFF707579)
val TgDivider = Color(0xFFE4E9ED)
val TgOnline = Color(0xFF5DC452)

private val LightColors = lightColorScheme(
    primary = TgAccent,
    onPrimary = Color.White,
    background = Color.White,
    onBackground = TgText,
    surface = Color.White,
    onSurface = TgText,
    surfaceVariant = TgChatBg,
)

@Composable
fun TasksbaseChatTheme(content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = LightColors, content = content)
}
