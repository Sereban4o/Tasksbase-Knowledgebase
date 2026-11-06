package com.tasksbase.chat.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

val TgAccent = Color(0xFF3390EC)
val TgAccentDark = Color(0xFF2B82D9)
val TgChatBg = Color(0xFFE6EBEE)
val TgOutgoing = Color(0xFFEEFFDE)
val TgText = Color(0xFF0F0F0F)
val TgSecondary = Color(0xFF707579)
val TgDivider = Color(0xFFE4E9ED)
val TgOnline = Color(0xFF5DC452)

/** Chat-specific colors that follow the selected theme. */
data class ChatPalette(
    val chatBg: Color,
    val outgoing: Color,
    val text: Color,
    val secondary: Color,
    val divider: Color,
    val surface: Color,
)

private val LightPalette = ChatPalette(
    chatBg = TgChatBg,
    outgoing = TgOutgoing,
    text = TgText,
    secondary = TgSecondary,
    divider = TgDivider,
    surface = Color.White,
)

private val DarkPalette = ChatPalette(
    chatBg = Color(0xFF0E1621),
    outgoing = Color(0xFF2B5278),
    text = Color(0xFFFFFFFF),
    secondary = Color(0xFF8A8F94),
    divider = Color(0xFF101921),
    surface = Color(0xFF17212B),
)

val LocalChatPalette = staticCompositionLocalOf { LightPalette }

private val LightColors = lightColorScheme(
    primary = TgAccent,
    onPrimary = Color.White,
    background = Color.White,
    onBackground = TgText,
    surface = Color.White,
    onSurface = TgText,
    surfaceVariant = TgChatBg,
)

private val DarkColors = darkColorScheme(
    primary = TgAccent,
    onPrimary = Color.White,
    background = Color(0xFF0E1621),
    onBackground = Color.White,
    surface = Color(0xFF17212B),
    onSurface = Color.White,
    surfaceVariant = Color(0xFF0E1621),
)

@Composable
fun TasksbaseChatTheme(dark: Boolean = false, content: @Composable () -> Unit) {
    CompositionLocalProvider(LocalChatPalette provides if (dark) DarkPalette else LightPalette) {
        MaterialTheme(
            colorScheme = if (dark) DarkColors else LightColors,
            content = content,
        )
    }
}
