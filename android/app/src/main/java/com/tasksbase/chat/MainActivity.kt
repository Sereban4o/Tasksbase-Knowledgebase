package com.tasksbase.chat

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import com.tasksbase.chat.ui.AppRoot
import com.tasksbase.chat.ui.theme.TasksbaseChatTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            TasksbaseChatTheme {
                AppRoot()
            }
        }
    }
}
