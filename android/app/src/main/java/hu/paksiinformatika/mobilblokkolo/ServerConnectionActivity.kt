package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.provider.Settings
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.textfield.TextInputEditText
import hu.paksiinformatika.mobilblokkolo.network.ApiClient
import hu.paksiinformatika.mobilblokkolo.network.DeviceRegisterRequest
import kotlinx.coroutines.launch
import java.security.MessageDigest

class ServerConnectionActivity : AppCompatActivity() {

    private val prefsName = "pi_gate_settings"

    private lateinit var etAdminPassword: TextInputEditText
    private lateinit var etServerAddress: TextInputEditText
    private lateinit var etServerPort: TextInputEditText
    private lateinit var etDeviceId: TextInputEditText
    private lateinit var tvConnectionStatus: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_server_connection)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val btnTestConnection =
            findViewById<Button>(R.id.btnTestConnection)

        val btnSaveServer =
            findViewById<Button>(R.id.btnSaveServer)

        etAdminPassword =
            findViewById(R.id.etAdminPassword)

        etServerAddress =
            findViewById(R.id.etServerAddress)

        etServerPort =
            findViewById(R.id.etServerPort)

        etDeviceId =
            findViewById(R.id.etDeviceId)

        tvConnectionStatus =
            findViewById(R.id.tvConnectionStatus)

        btnBack.setOnClickListener {
            finish()
        }

        loadSettings()

        btnSaveServer.setOnClickListener {
            saveSettings()
        }

        btnTestConnection.setOnClickListener {
            testConnectionAndRegisterDevice()
        }
    }

    private fun loadSettings() {

        val prefs =
            getSharedPreferences(
                prefsName,
                MODE_PRIVATE
            )

        val serverAddress =
            prefs.getString(
                "server_address",
                ""
            ) ?: ""

        val serverPort =
            prefs.getString(
                "server_port",
                "443"
            ) ?: "443"

        val deviceId =
            getPiGateDeviceId()

        if (serverAddress.isNotBlank()) {
            etServerAddress.setText(serverAddress)
        } else {
            etServerAddress.setText("https://")
        }

        etServerPort.setText(serverPort)
        etDeviceId.setText(deviceId)

        val apiToken =
            prefs.getString(
                "device_api_token",
                ""
            ) ?: ""

        tvConnectionStatus.text =
            if (apiToken.isNotBlank()) {
                "Regisztrálva"
            } else {
                "Nincs regisztrálva"
            }
    }

    private fun getPiGateDeviceId(): String {

        val androidId =
            Settings.Secure.getString(
                contentResolver,
                Settings.Secure.ANDROID_ID
            )

        return "PIGATE-${androidId.uppercase()}"
    }

    private fun saveSettings() {

        val password =
            etAdminPassword.text
                ?.toString()
                ?.trim()
                ?: ""

        val serverAddress =
            etServerAddress.text
                ?.toString()
                ?.trim()
                ?: ""

        val serverPort =
            etServerPort.text
                ?.toString()
                ?.trim()
                ?: ""

        if (password.isBlank()) {

            Toast.makeText(
                this,
                "Add meg az admin jelszót.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (
            serverAddress.isBlank() ||
            serverAddress == "https://"
        ) {

            Toast.makeText(
                this,
                "Add meg a szerver címét.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (serverPort.isBlank()) {

            Toast.makeText(
                this,
                "Add meg a portot.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        val prefs =
            getSharedPreferences(
                prefsName,
                MODE_PRIVATE
            )

        val storedPasswordHash =
            prefs.getString(
                "admin_password_hash",
                null
            )

        if (storedPasswordHash == null) {

            prefs.edit()
                .putString(
                    "admin_password_hash",
                    sha256(password)
                )
                .putString(
                    "server_address",
                    serverAddress
                )
                .putString(
                    "server_port",
                    serverPort
                )
                .apply()

            etAdminPassword.text?.clear()

            Toast.makeText(
                this,
                "Szerverbeállítás és admin jelszó elmentve.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        if (
            sha256(password) !=
            storedPasswordHash
        ) {

            Toast.makeText(
                this,
                "Hibás admin jelszó.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        prefs.edit()
            .putString(
                "server_address",
                serverAddress
            )
            .putString(
                "server_port",
                serverPort
            )
            .apply()

        etAdminPassword.text?.clear()

        Toast.makeText(
            this,
            "Szerverbeállítások elmentve.",
            Toast.LENGTH_LONG
        ).show()
    }

    private fun testConnectionAndRegisterDevice() {

        val prefs =
            getSharedPreferences(
                prefsName,
                MODE_PRIVATE
            )

        val serverAddress =
            etServerAddress.text
                ?.toString()
                ?.trim()
                ?: ""

        val serverPort =
            etServerPort.text
                ?.toString()
                ?.trim()
                ?: ""

        if (
            serverAddress.isBlank() ||
            serverAddress == "https://"
        ) {

            Toast.makeText(
                this,
                "Nincs megadva szervercím.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        prefs.edit()
            .putString(
                "server_address",
                serverAddress
            )
            .putString(
                "server_port",
                serverPort
            )
            .apply()

        tvConnectionStatus.text =
            "Kapcsolódás..."

        lifecycleScope.launch {

            try {

                val api =
                    ApiClient.create(
                        this@ServerConnectionActivity
                    )

                val response =
                    api.registerDevice(
                        DeviceRegisterRequest(
                            company_id = 1,
                            device_uid = getPiGateDeviceId(),
                            name = "PI Gate PDA",
                            platform = "android",
                            app_version = "v${BuildConfig.VERSION_NAME}"
                        )
                    )

                if (!response.success) {

                    throw Exception(
                        "A szerver sikertelen választ adott."
                    )
                }

                prefs.edit()
                    .putString(
                        "device_api_token",
                        response.token
                    )
                    .putLong(
                        "server_device_id",
                        response.device.id
                    )
                    .putLong(
                        "company_id",
                        response.company.id
                    )
                    .putString(
                        "company_name",
                        response.company.name
                    )
                    .apply()

                tvConnectionStatus.text =
                    "Kapcsolat OK"

                Toast.makeText(
                    this@ServerConnectionActivity,
                    "Eszköz sikeresen regisztrálva.",
                    Toast.LENGTH_LONG
                ).show()

            } catch (e: Exception) {

                tvConnectionStatus.text =
                    "Kapcsolati hiba"

                Toast.makeText(
                    this@ServerConnectionActivity,
                    "Hiba: ${e.message}",
                    Toast.LENGTH_LONG
                ).show()
            }
        }
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
