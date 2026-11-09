package com.tasksbase.chat.di

import com.tasksbase.chat.data.local.Account
import com.tasksbase.chat.data.remote.UserDto
import kotlinx.coroutines.flow.MutableStateFlow

/** In-memory session shared across the app. Persistence lives in AccountStore. */
object Session {
    @Volatile
    var token: String? = null

    @Volatile
    var currentUser: UserDto? = null

    /** Currently active account (server + credentials). */
    @Volatile
    var activeAccount: Account? = null

    /** Chat currently opened in the UI; notifications are suppressed for it. */
    @Volatile
    var activeChatId: Int? = null

    /** Bumped whenever the chat list must be reloaded (e.g. a chat was deleted). */
    val chatListVersion = MutableStateFlow(0)

    fun bumpChatList() {
        chatListVersion.value += 1
    }

    fun reset() {
        token = null
        currentUser = null
        activeAccount = null
        activeChatId = null
        bumpChatList()
    }
}
