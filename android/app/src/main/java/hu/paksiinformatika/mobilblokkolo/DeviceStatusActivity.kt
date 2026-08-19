package hu.paksiinformatika.mobilblokkolo

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.nfc.NfcAdapter
import android.os.Bundle
import android.os.StatFs
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.launch
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class DeviceStatusActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_device_status)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        btnBack.setOnClickListener {
            finish()
        }

        checkNfc()
        checkCamera()
        checkGps()
        checkInternet()
        checkDatabase()
        checkStorage()

        checkServer()
        checkSync()
    }

    private fun checkNfc() {

        val tv =
            findViewById<TextView>(R.id.tvStatusNfc)

        val adapter =
            NfcAdapter.getDefaultAdapter(this)

        tv.text =
            when {
                adapter == null ->
                    "Nem támogatott"

                !adapter.isEnabled ->
                    "Kikapcsolva"

                else ->
                    "OK"
            }
    }

    private fun checkCamera() {

        val tv =
            findViewById<TextView>(R.id.tvStatusCamera)

        val hasCamera =
            packageManager.hasSystemFeature(
                PackageManager.FEATURE_CAMERA_FRONT
            )

        val permissionGranted =
            ContextCompat.checkSelfPermission(
                this,
                Manifest.permission.CAMERA
            ) == PackageManager.PERMISSION_GRANTED

        tv.text =
            when {
                !hasCamera ->
                    "Nincs kamera"

                !permissionGranted ->
                    "Nincs engedély"

                else ->
                    "OK"
            }
    }

    private fun checkGps() {

        val tv =
            findViewById<TextView>(R.id.tvStatusGps)

        val locationManager =
            getSystemService(
                Context.LOCATION_SERVICE
            ) as LocationManager

        val hasPermission =
            ContextCompat.checkSelfPermission(
                this,
                Manifest.permission.ACCESS_FINE_LOCATION
            ) == PackageManager.PERMISSION_GRANTED

        val enabled =
            locationManager.isProviderEnabled(
                LocationManager.GPS_PROVIDER
            )

        tv.text =
            when {
                !hasPermission ->
                    "Nincs engedély"

                !enabled ->
                    "Kikapcsolva"

                else ->
                    "OK"
            }
    }

    private fun checkInternet() {

        val tv =
            findViewById<TextView>(R.id.tvStatusInternet)

        val connectivityManager =
            getSystemService(
                Context.CONNECTIVITY_SERVICE
            ) as ConnectivityManager

        val network =
            connectivityManager.activeNetwork

        if (network == null) {
            tv.text = "Nincs kapcsolat"
            return
        }

        val capabilities =
            connectivityManager.getNetworkCapabilities(
                network
            )

        val connected =
            capabilities?.hasCapability(
                NetworkCapabilities.NET_CAPABILITY_INTERNET
            ) == true

        tv.text =
            if (connected) {
                "OK"
            } else {
                "Nincs internet"
            }
    }

    private fun checkServer() {

        val prefs =
            getSharedPreferences(
                "pi_gate_settings",
                MODE_PRIVATE
            )

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

        findViewById<TextView>(R.id.tvStatusServer).text =
            when {

                serverAddress.isBlank() ->
                    "Nincs beállítva"

                apiToken.isBlank() ->
                    "Nincs regisztrálva"

                else ->
                    serverAddress
            }
    }

    private fun checkSync() {

        val prefs =
            getSharedPreferences(
                "pi_gate_settings",
                MODE_PRIVATE
            )

        val lastSync =
            prefs.getLong(
                "last_auto_sync",
                0L
            )

        findViewById<TextView>(R.id.tvStatusSync).text =
            if (lastSync > 0L) {

                val formatter =
                    SimpleDateFormat(
                        "yyyy-MM-dd HH:mm:ss",
                        Locale.getDefault()
                    )

                formatter.format(
                    Date(lastSync)
                )

            } else {

                "Még nem történt"
            }
    }

    private fun checkDatabase() {

        val tv =
            findViewById<TextView>(
                R.id.tvStatusDatabase
            )

        lifecycleScope.launch {

            try {

                val db =
                    DatabaseProvider.getDatabase(
                        this@DeviceStatusActivity
                    )

                db.employeeDao().getAll()

                tv.text = "OK"

            } catch (e: Exception) {

                tv.text = "Hiba"
            }
        }
    }

    private fun checkStorage() {

        val tv =
            findViewById<TextView>(
                R.id.tvStatusStorage
            )

        val path =
            File(filesDir.absolutePath)

        val stat =
            StatFs(path.path)

        val availableBytes =
            stat.availableBytes

        val availableGb =
            availableBytes.toDouble() /
                    1024 /
                    1024 /
                    1024

        tv.text =
            String.format(
                "%.1f GB",
                availableGb
            )
    }
}
