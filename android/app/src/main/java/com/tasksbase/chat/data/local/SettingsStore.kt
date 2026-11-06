package com.tasksbase.chat.data.local

import android.content.Context
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first

private val Context.settingsDataStore by preferencesDataStore(name = "chat_settings")

/** Persists user interface preferences (theme, etc.). */
class SettingsStore(private val context: Context) {

    private companion object {
        val KEY_DARK_THEME = booleanPreferencesKey("dark_theme")
    }

    suspend fun loadDarkTheme(): Boolean =
        context.settingsDataStore.data.first()[KEY_DARK_THEME] ?: false

    suspend fun saveDarkTheme(enabled: Boolean) {
        context.settingsDataStore.edit { prefs -> prefs[KEY_DARK_THEME] = enabled }
    }
}
