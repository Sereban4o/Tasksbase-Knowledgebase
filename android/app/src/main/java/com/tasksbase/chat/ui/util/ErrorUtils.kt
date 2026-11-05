package com.tasksbase.chat.ui.util

import java.net.ConnectException
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import retrofit2.HttpException

fun friendlyError(e: Throwable): String = when (e) {
    is HttpException -> when (e.code()) {
        401 -> "Неверный email или пароль"
        403 -> "Доступ запрещён"
        422 -> "Некорректные данные"
        else -> "Ошибка сервера (${e.code()})"
    }
    is UnknownHostException, is ConnectException, is SocketTimeoutException ->
        "Нет соединения с сервером"
    else -> e.message ?: "Неизвестная ошибка"
}
