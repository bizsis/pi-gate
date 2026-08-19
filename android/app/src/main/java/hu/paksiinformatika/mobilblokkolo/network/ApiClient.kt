package hu.paksiinformatika.mobilblokkolo.network

import android.content.Context
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

object ApiClient {

    fun create(
        context: Context
    ): ApiService {

        val prefs =
            context.getSharedPreferences(
                "pi_gate_settings",
                Context.MODE_PRIVATE
            )

        var serverAddress =
            prefs.getString(
                "server_address",
                ""
            ) ?: ""

        val serverPort =
            prefs.getString(
                "server_port",
                "443"
            ) ?: "443"

        if (serverAddress.isBlank()) {
            throw IllegalStateException(
                "Nincs beállítva szervercím."
            )
        }

        if (!serverAddress.endsWith("/")) {
            serverAddress += "/"
        }

        val baseUrl =
            if (
                serverPort == "443" ||
                serverPort.isBlank()
            ) {
                serverAddress
            } else {
                serverAddress
                    .removeSuffix("/") +
                        ":$serverPort/"
            }

        val logging =
            HttpLoggingInterceptor().apply {
                level =
                    HttpLoggingInterceptor.Level.BASIC
            }

        val client =
            OkHttpClient.Builder()
                .addInterceptor(logging)
                .build()

        val retrofit =
            Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(
                    GsonConverterFactory.create()
                )
                .build()

        return retrofit.create(
            ApiService::class.java
        )
    }
}

