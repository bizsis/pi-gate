package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class SyncActivity : AppCompatActivity() {

    private lateinit var tvLastSync: TextView
    private lateinit var tvPendingEvents: TextView
    private lateinit var tvEmployeeSync: TextView
    private lateinit var tvServerStatus: TextView
    private lateinit var btnSyncNow: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_sync)

        val btnBack =
            findViewById<Button>(
                R.id.btnBack
            )

        btnSyncNow =
            findViewById(
                R.id.btnSyncNow
            )

        tvLastSync =
            findViewById(
                R.id.tvLastSync
            )

        tvPendingEvents =
            findViewById(
                R.id.tvPendingEvents
            )

        tvEmployeeSync =
            findViewById(
                R.id.tvEmployeeSync
            )

        tvServerStatus =
            findViewById(
                R.id.tvServerStatus
            )

        btnBack.setOnClickListener {
            finish()
        }

        btnSyncNow.setOnClickListener {

            SyncManager.requestSync(
                this
            )

            Toast.makeText(
                this,
                "Szinkronizálás elindítva.",
                Toast.LENGTH_SHORT
            ).show()

            lifecycleScope.launch {

                repeat(10) {

                    delay(1000)

                    loadLocalStatus()
                }
            }
        }

        loadLocalStatus()
    }

    override fun onResume() {
        super.onResume()

        loadLocalStatus()
    }

    private fun loadLocalStatus() {

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(
                    this@SyncActivity
                )

            val pendingEvents =
                db.eventDao()
                    .getUnsynced()

            val employees =
                db.employeeDao()
                    .getActive()

            val prefs =
                getSharedPreferences(
                    "pi_gate_settings",
                    MODE_PRIVATE
                )

            // =====================================================
            // FELTÖLTÉSRE VÁRÓ ESEMÉNYEK
            // =====================================================

            val pendingEventCount =
                pendingEvents.size

            val pendingPhotoCount =
                pendingEvents.count {
                    !it.photoPath.isNullOrBlank()
                }

            tvPendingEvents.text =
                pendingEventCount.toString()

            // =====================================================
            // SZINKRONIZÁLÁSI ÁLLAPOT
            // =====================================================

            val syncStatus =
                when {

                    pendingEventCount == 0 -> {
                        "Minden adat szinkronizálva"
                    }

                    else -> {
                        "Szinkronizálásra vár"
                    }
                }

            tvEmployeeSync.text =
                "Aktív dolgozók helyben: ${employees.size}\n\n" +
                        "Feltöltésre váró események: $pendingEventCount\n" +
                        "Feltöltésre váró fotók: $pendingPhotoCount\n\n" +
                        "Legutóbbi letöltés: " +
                        "${prefs.getInt("last_sync_downloaded_new", 0)} új, " +
                        "${prefs.getInt("last_sync_downloaded_updated", 0)} frissített dolgozó\n" +
                        "Blokkolás letöltés: " +
                        "${prefs.getInt("last_sync_downloaded_events_new", 0)} új, " +
                        "${prefs.getInt("last_sync_downloaded_events_updated", 0)} frissített\n" +
                        "Állapot: $syncStatus"

            // =====================================================
            // SZERVER KAPCSOLAT
            // =====================================================

            val serverAddress =
                prefs.getString(
                    "server_address",
                    ""
                ) ?: ""

            val apiToken =
                prefs.getString(
                    "device_api_token",
                    ""
                ) ?: ""

            tvServerStatus.text =
                when {

                    serverAddress.isBlank() ->
                        "Nincs beállítva"

                    apiToken.isBlank() ->
                        "Eszköz nincs regisztrálva"

                    else ->
                        serverAddress
                }

            // =====================================================
            // UTOLSÓ SIKERES SZINKRON
            // =====================================================

            val lastSync =
                prefs.getLong(
                    "last_auto_sync",
                    0L
                )

            if (lastSync > 0L) {

                val formatter =
                    SimpleDateFormat(
                        "yyyy-MM-dd HH:mm:ss",
                        Locale.getDefault()
                    )

                tvLastSync.text =
                    formatter.format(
                        Date(lastSync)
                    )

            } else {

                tvLastSync.text =
                    "Még nem történt"
            }
        }
    }
}
