package hu.paksiinformatika.mobilblokkolo

import android.content.Context
import java.security.MessageDigest

object AdminAuth {

    private const val PREFS_NAME = "pi_gate_settings"
    private const val ADMIN_PASSWORD_HASH = "admin_password_hash"

    fun hasPassword(context: Context): Boolean {
        return context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(ADMIN_PASSWORD_HASH, null) != null
    }

    fun verify(context: Context, password: String): Boolean {
        val storedHash =
            context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
                .getString(ADMIN_PASSWORD_HASH, null)
                ?: return false

        return sha256(password) == storedHash
    }

    fun sha256(value: String): String {
        val digest =
            MessageDigest.getInstance("SHA-256")

        val bytes =
            digest.digest(value.toByteArray())

        return bytes.joinToString("") {
            "%02x".format(it)
        }
    }
}

