package com.tasksbase.chat.di

import com.tasksbase.chat.data.remote.UserDto

/** In-memory session shared across the app. Persistence lives in TokenStore. */
object Session {
    @Volatile
    var token: String? = null

    @Volatile
    var currentUser: UserDto? = null

    fun reset() {
        token = null
        currentUser = null
    }
}
