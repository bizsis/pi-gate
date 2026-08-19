package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.provider.Settings
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.textfield.TextInputEditText
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.launch
import java.security.MessageDigest

class AdminSettingsActivity : AppCompatActivity() {

    private val prefsName = "pi_gate_settings"

    private lateinit var etCurrentPassword: TextInputEditText
    private lateinit var etNewPassword: TextInputEditText
    private lateinit var etNewPasswordAgain: TextInputEditText

    private lateinit var tvDeviceId: TextView
    private lateinit var tvServer: TextView
    private lateinit var tvDatabaseStatus: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_admin_settings)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val btnChangePassword =
            findViewById<Button>(R.id.btnChangePassword)

        etCurrentPassword =
            findViewById(R.id.etCurrentPassword)

        etNewPassword =
            findViewById(R.id.etNewPassword)

        etNewPasswordAgain =
            findViewById(R.id.etNewPasswordAgain)

        tvDeviceId =
            findViewById(R.id.tvDeviceId)

        tvServer =
            findViewById(R.id.tvServer)

        tvDatabaseStatus =
            findViewById(R.id.tvDatabaseStatus)

        btnBack.setOnClickListener {
            finish()
        }

        btnChangePassword.setOnClickListener {
            changeAdminPassword()
        }

        loadDeviceInfo()
        loadServerInfo()
        checkDatabase()
    }

    private fun loadDeviceInfo() {

        val androidId =
            Settings.Secure.getString(
                contentResolver,
                Settings.Secure.ANDROID_ID
            )

        tvDeviceId.text =
            "PIGATE-${androidId.uppercase()}"
    }

    private fun loadServerInfo() {

        val prefs =
            getSharedPreferences(
                prefsName,
                MODE_PRIVATE
            )

        val address =
            prefs.getString(
                "server_address",
                ""
            ) ?: ""

        val port =
            prefs.getString(
                "server_port",
                ""
            ) ?: ""

        tvServer.text =
            if (address.isNotBlank()) {
                if (port.isNotBlank()) {
                    "$address:$port"
                } else {
                    address
                }
            } else {
                "Nincs beállítva"
            }
    }

    private fun checkDatabase() {

        lifecycleScope.launch {

            try {

                val db =
                    DatabaseProvider.getDatabase(
                        this@AdminSettingsActivity
                    )

                val employees =
                    db.employeeDao().getAll()

                val events =
                    db.eventDao().getAll()

                tvDatabaseStatus.text =
                    "OK – ${employees.size} dolgozó, ${events.size} esemény"

            } catch (e: Exception) {

                tvDatabaseStatus.text =
                    "Hiba"
            }
        }
    }

    private fun changeAdminPassword() {

        val currentPassword =
            etCurrentPassword.text
                ?.toString()
                ?.trim()
                ?: ""

        val newPassword =
            etNewPassword.text
                ?.toString()
                ?.trim()
                ?: ""

        val newPasswordAgain =
            etNewPasswordAgain.text
                ?.toString()
                ?.trim()
                ?: ""

        val prefs =
            getSharedPreferences(
                prefsName,
                MODE_PRIVATE
            )

        val storedHash =
            prefs.getString(
                "admin_password_hash",
                null
            )

        if (storedHash == null) {

            Toast.makeText(
                this,
                "Még nincs admin jelszó beállítva.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (currentPassword.isBlank()) {

            Toast.makeText(
                this,
                "Add meg a jelenlegi admin jelszót.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (sha256(currentPassword) != storedHash) {

            Toast.makeText(
                this,
                "Hibás jelenlegi admin jelszó.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (newPassword.length < 6) {

            Toast.makeText(
                this,
                "Az új jelszó legalább 6 karakter legyen.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (newPassword != newPasswordAgain) {

            Toast.makeText(
                this,
                "A két új jelszó nem egyezik.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        prefs.edit()
            .putString(
                "admin_password_hash",
                sha256(newPassword)
            )
            .apply()

        etCurrentPassword.text?.clear()
        etNewPassword.text?.clear()
        etNewPasswordAgain.text?.clear()

        Toast.makeText(
            this,
            "Admin jelszó módosítva.",
            Toast.LENGTH_LONG
        ).show()
    }

    private fun sha256(value: String): String {

        val digest =
            MessageDigest.getInstance("SHA-256")

        val bytes =
            digest.digest(
                value.toByteArray()
            )

        return bytes.joinToString("") {
            "%02x".format(it)
        }
    }
}