package hu.paksiinformatika.mobilblokkolo

import android.Manifest
import android.app.AlertDialog
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.Handler
import android.os.Looper
import android.nfc.NfcAdapter
import android.nfc.Tag
import android.os.Bundle
import android.view.View
import android.widget.EditText
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageCapture
import androidx.camera.core.ImageCaptureException
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import hu.paksiinformatika.mobilblokkolo.data.local.EmployeeEntity
import hu.paksiinformatika.mobilblokkolo.data.local.EventEntity
import kotlinx.coroutines.launch
import java.io.File
import java.math.BigInteger
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.UUID

class MainActivity : AppCompatActivity(), NfcAdapter.ReaderCallback {

    private var nfcAdapter: NfcAdapter? = null
    private var imageCapture: ImageCapture? = null

    // true = ÉRKEZÉS
    // false = TÁVOZÁS
    private var isArrival = true

    private lateinit var directionToggle: View
    private lateinit var directionThumb: View
    private lateinit var tvDirectionActive: TextView
    private lateinit var tvDirectionLeft: TextView
    private lateinit var tvDirectionRight: TextView

    private lateinit var tvStatusTime: TextView
    private lateinit var imgSyncStatusIcon: ImageView
    private lateinit var tvSyncBadge: TextView
    private lateinit var imgNetworkStatus: ImageView
    private lateinit var tvNetworkLabel: TextView
    private lateinit var imgUpdateStatus: ImageView
    private lateinit var imgNotificationStatus: ImageView
    private lateinit var notificationDot: View

    private val statusHandler =
        Handler(Looper.getMainLooper())

    private val statusRunnable =
        object : Runnable {
            override fun run() {
                updateStatusBar()

                statusHandler.postDelayed(
                    this,
                    30_000L
                )
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        applyFullscreenMode()

        bindStatusBar()

        KioskManager.configureDeviceOwnerPolicies(this)

        nfcAdapter =
            NfcAdapter.getDefaultAdapter(this)

        // =====================================================
        // JOGOSULTSÁGOK
        // =====================================================

        val permissions =
            arrayOf(
                Manifest.permission.CAMERA,
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            )

        val missingPermissions =
            permissions.filter {
                ContextCompat.checkSelfPermission(
                    this,
                    it
                ) != PackageManager.PERMISSION_GRANTED
            }

        if (missingPermissions.isNotEmpty()) {

            ActivityCompat.requestPermissions(
                this,
                missingPermissions.toTypedArray(),
                100
            )
        }

        if (nfcAdapter == null) {

            Toast.makeText(
                this,
                "Nincs NFC ezen az eszközön",
                Toast.LENGTH_LONG
            ).show()
        }

        // =====================================================
        // ELSŐ INDÍTÁSOS TESZTADAT
        // Csak regisztrálatlan, üres adatbázisú eszközön.
        // Szerverhez kötött PDA-n a dolgozótörzs központi adat.
        // =====================================================

        lifecycleScope.launch {

            val prefs =
                getSharedPreferences(
                    "pi_gate_settings",
                    MODE_PRIVATE
                )

            val apiToken =
                prefs.getString(
                    "device_api_token",
                    ""
                ) ?: ""

            val db =
                DatabaseProvider.getDatabase(
                    this@MainActivity
                )

            if (
                apiToken.isBlank() &&
                db.employeeDao()
                    .getAll()
                    .isEmpty()
            ) {

                db.employeeDao().upsert(
                    EmployeeEntity(
                        id = -1,
                        name = "Bizsi Sándor",
                        cardNumber = "340016406",
                        companyId = 1,
                        active = true,
                        updatedAt = System.currentTimeMillis()
                    )
                )
            }
        }

        // =====================================================
        // HAMBURGER
        // =====================================================

        val btnHamburger =
            findViewById<TextView>(
                R.id.btnHamburger
            )

        val hamburgerMenu =
            findViewById<View>(
                R.id.hamburgerMenu
            )

        // =====================================================
        // ÉRKEZÉS / TÁVOZÁS KAPCSOLÓ
        // =====================================================

        directionToggle =
            findViewById(
                R.id.directionToggle
            )

        directionThumb =
            findViewById(
                R.id.directionThumb
            )

        tvDirectionActive =
            findViewById(
                R.id.tvDirectionActive
            )

        tvDirectionLeft =
            findViewById(
                R.id.tvDirectionLeft
            )

        tvDirectionRight =
            findViewById(
                R.id.tvDirectionRight
            )

        directionToggle.setOnClickListener {

            isArrival =
                !isArrival

            updateDirectionToggle()
        }

        loadDefaultDirection()

        // =====================================================
        // HAMBURGER NYITÁS / ZÁRÁS
        // =====================================================

        btnHamburger.setOnClickListener {

            hamburgerMenu.visibility =
                if (
                    hamburgerMenu.visibility ==
                    View.VISIBLE
                ) {
                    View.GONE
                } else {
                    View.VISIBLE
                }
        }

        // =====================================================
        // DOLGOZÓK
        // =====================================================

        val menuEmployees =
            findViewById<TextView>(
                R.id.menuEmployees
            )

        menuEmployees.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    EmployeesActivity::class.java
                )
            )
        }

        // =====================================================
        // MUNKAIDŐ
        // =====================================================

        val menuWorkTime =
            findViewById<TextView>(
                R.id.menuWorkTime
            )

        menuWorkTime.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    WorkTimeActivity::class.java
                )
            )
        }

        // =====================================================
        // ESZKÖZ ÁLLAPOT
        // =====================================================

        val menuDeviceStatus =
            findViewById<TextView>(
                R.id.menuDeviceStatus
            )

        menuDeviceStatus.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    DeviceStatusActivity::class.java
                )
            )
        }

        // =====================================================
        // SZINKRONIZÁLÁS
        // =====================================================

        val menuSync =
            findViewById<TextView>(
                R.id.menuSync
            )

        menuSync.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    SyncActivity::class.java
                )
            )
        }

        // =====================================================
        // SZERVER KAPCSOLAT
        // =====================================================

        val menuServer =
            findViewById<TextView>(
                R.id.menuServer
            )

        menuServer.setOnClickListener {

            requestAdminPassword(
                "Szerver kapcsolat",
                allowWhenNoPassword = true
            ) {
                hamburgerMenu.visibility =
                    View.GONE

                startActivity(
                    Intent(
                        this,
                        ServerConnectionActivity::class.java
                    )
                )
            }
        }

        // =====================================================
        // WI-FI BEÁLLÍTÁS
        // =====================================================

        val menuWifi =
            findViewById<TextView>(
                R.id.menuWifi
            )

        menuWifi.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            KioskManager.openWifiSettings(this)
        }

        // =====================================================
        // ADMIN BEÁLLÍTÁSOK
        // =====================================================

        val menuAdmin =
            findViewById<TextView>(
                R.id.menuAdmin
            )

        menuAdmin.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    AdminSettingsActivity::class.java
                )
            )
        }

        // =====================================================
        // KIOSK FELOLDÁS
        // =====================================================

        val menuExitKiosk =
            findViewById<TextView>(
                R.id.menuExitKiosk
            )

        menuExitKiosk.visibility =
            View.VISIBLE

        menuExitKiosk.setOnClickListener {

            exitProgramWithPassword()
        }

        // =====================================================
        // NÉVJEGY
        // =====================================================

        val menuAbout =
            findViewById<TextView>(
                R.id.menuAbout
            )

        menuAbout.setOnClickListener {

            hamburgerMenu.visibility =
                View.GONE

            startActivity(
                Intent(
                    this,
                    AboutActivity::class.java
                )
            )
        }

        // =====================================================
        // ALSÓ MENÜ - NAPLÓ
        // =====================================================

        val navLog =
            findViewById<TextView>(
                R.id.navLog
            )

        navLog.setOnClickListener {

            startActivity(
                Intent(
                    this,
                    EventLogActivity::class.java
                )
            )
        }

        // =====================================================
        // ALSÓ MENÜ - ÁLLAPOT
        // =====================================================

        val navStatus =
            findViewById<TextView>(
                R.id.navStatus
            )

        navStatus.setOnClickListener {

            startActivity(
                Intent(
                    this,
                    DeviceStatusActivity::class.java
                )
            )
        }

        // =====================================================
        // KAMERA
        // =====================================================

        startCamera()

        KioskManager.startKioskIfOwner(this)
    }


    private fun applyFullscreenMode() {

        window.decorView.systemUiVisibility =
            View.SYSTEM_UI_FLAG_FULLSCREEN or
                    View.SYSTEM_UI_FLAG_HIDE_NAVIGATION or
                    View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY or
                    View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN or
                    View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION or
                    View.SYSTEM_UI_FLAG_LAYOUT_STABLE
    }

    // =========================================================
    // FELSŐ STÁTUSZSÁV
    // =========================================================

    private fun bindStatusBar() {

        tvStatusTime =
            findViewById(
                R.id.tvStatusTime
            )

        imgSyncStatusIcon =
            findViewById(
                R.id.imgSyncStatusIcon
            )

        tvSyncBadge =
            findViewById(
                R.id.tvSyncBadge
            )

        imgNetworkStatus =
            findViewById(
                R.id.imgNetworkStatus
            )

        tvNetworkLabel =
            findViewById(
                R.id.tvNetworkLabel
            )

        imgUpdateStatus =
            findViewById(
                R.id.imgUpdateStatus
            )

        imgNotificationStatus =
            findViewById(
                R.id.imgNotificationStatus
            )

        notificationDot =
            findViewById(
                R.id.notificationDot
            )

        imgUpdateStatus.setImageResource(
            R.drawable.ic_update_ok
        )

        imgNotificationStatus.setImageResource(
            R.drawable.ic_bell_ok
        )

        notificationDot.visibility =
            View.GONE

        updateStatusBar()
    }

    private fun updateStatusBar() {

        tvStatusTime.text =
            SimpleDateFormat(
                "HH:mm",
                Locale.getDefault()
            ).format(
                Date()
            )

        updateNetworkStatus()
        updatePendingSyncStatus()
    }

    private fun updateNetworkStatus() {

        val connectivityManager =
            getSystemService(
                Context.CONNECTIVITY_SERVICE
            ) as ConnectivityManager

        val network =
            connectivityManager.activeNetwork

        val capabilities =
            network
                ?.let {
                    connectivityManager.getNetworkCapabilities(
                        it
                    )
                }

        when {

            capabilities == null -> {
                imgNetworkStatus.setImageResource(
                    R.drawable.ic_network_offline
                )

                tvNetworkLabel.text =
                    ""
            }

            capabilities.hasTransport(
                NetworkCapabilities.TRANSPORT_WIFI
            ) -> {
                imgNetworkStatus.setImageResource(
                    R.drawable.ic_wifi_status
                )

                tvNetworkLabel.text =
                    ""
            }

            capabilities.hasTransport(
                NetworkCapabilities.TRANSPORT_CELLULAR
            ) -> {
                imgNetworkStatus.setImageResource(
                    R.drawable.ic_cellular_status
                )

                tvNetworkLabel.text =
                    "4G"
            }

            else -> {
                imgNetworkStatus.setImageResource(
                    R.drawable.ic_wifi_status
                )

                tvNetworkLabel.text =
                    ""
            }
        }
    }

    private fun updatePendingSyncStatus() {

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(
                    this@MainActivity
                )

            val pendingEvents =
                db.eventDao()
                    .getUnsynced()

            val pendingCount =
                pendingEvents.size

            if (pendingCount == 0) {

                imgSyncStatusIcon.setImageResource(
                    R.drawable.ic_sync_cloud_ok
                )

                tvSyncBadge.visibility =
                    View.GONE

            } else {

                imgSyncStatusIcon.setImageResource(
                    R.drawable.ic_sync_cloud_pending
                )

                tvSyncBadge.text =
                    if (pendingCount > 99) {
                        "99+"
                    } else {
                        pendingCount.toString()
                    }

                tvSyncBadge.visibility =
                    View.VISIBLE
            }
        }
    }
    // =========================================================
    // ÉRKEZÉS / TÁVOZÁS ALAPÉRTELMEZÉS
    // =========================================================

    private fun loadDefaultDirection() {

        val now =
            java.util.Calendar.getInstance()

        val hour =
            now.get(java.util.Calendar.HOUR_OF_DAY)

        val minute =
            now.get(java.util.Calendar.MINUTE)

        isArrival =
            when {

                hour < 13 -> true

                hour == 13 && minute == 0 -> true

                else -> false
            }

        updateDirectionToggle()
    }

    // =========================================================
    // BILLENŐKAPCSOLÓ KIRAJZOLÁSA
    // =========================================================

    private fun updateDirectionToggle() {

        directionToggle.post {

            val density =
                resources.displayMetrics.density

            val targetX =
                if (isArrival) {

                    0f

                } else {

                    directionToggle.width -
                            directionThumb.width -
                            12 * density
                }

            directionThumb.animate()
                .translationX(targetX)
                .setDuration(180)
                .start()

            tvDirectionActive.animate()
                .translationX(targetX)
                .setDuration(180)
                .start()

            tvDirectionActive.text =
                if (isArrival) {
                    "ÉRKEZÉS"
                } else {
                    "TÁVOZÁS"
                }

            tvDirectionLeft.setTextColor(
                if (isArrival) {
                    0xFFFFFFFF.toInt()
                } else {
                    0xFF111827.toInt()
                }
            )

            tvDirectionRight.setTextColor(
                if (isArrival) {
                    0xFF687386.toInt()
                } else {
                    0xFFFFFFFF.toInt()
                }
            )
        }
    }

    // =========================================================
    // NFC READER
    // =========================================================

    override fun onResume() {
        super.onResume()

        loadDefaultDirection()

        applyFullscreenMode()

        KioskManager.startKioskIfOwner(this)

        statusHandler.removeCallbacks(statusRunnable)
        statusHandler.post(statusRunnable)

        nfcAdapter?.enableReaderMode(
            this,
            this,
            NfcAdapter.FLAG_READER_NFC_A or
                    NfcAdapter.FLAG_READER_SKIP_NDEF_CHECK,
            null
        )
    }

    override fun onBackPressed() {
        exitProgramWithPassword()
    }

    private fun exitProgramWithPassword() {
        requestAdminPassword(
            "Kilépés",
            allowWhenNoPassword = true
        ) {
            KioskManager.unlockForAdminExit(this)

            KioskManager.openHome(this)

            finishAndRemoveTask()
        }
    }

    private fun requestAdminPassword(
        title: String,
        allowWhenNoPassword: Boolean,
        onSuccess: () -> Unit
    ) {

        if (!AdminAuth.hasPassword(this)) {
            if (allowWhenNoPassword) {
                Toast.makeText(
                    this,
                    "Még nincs admin jelszó. Első beállításhoz az admin menü megnyílt.",
                    Toast.LENGTH_LONG
                ).show()

                onSuccess()

                return
            }

            Toast.makeText(
                this,
                "Előbb állíts be admin jelszót a szerverkapcsolatnál.",
                Toast.LENGTH_LONG
            ).show()

            return
        }

        val input =
            EditText(this).apply {
                hint =
                    "Admin jelszó"
                inputType =
                    android.text.InputType.TYPE_CLASS_TEXT or
                            android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
            }

        AlertDialog.Builder(this)
            .setTitle(title)
            .setView(input)
            .setNegativeButton("Mégse", null)
            .setPositiveButton("Megnyitás") { _, _ ->

                val password =
                    input.text
                        ?.toString()
                        ?.trim()
                        ?: ""

                if (AdminAuth.verify(this, password)) {
                    onSuccess()
                } else {
                    Toast.makeText(
                        this,
                        "Hibás admin jelszó.",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
            .show()
    }

    override fun onPause() {
        super.onPause()

        nfcAdapter?.disableReaderMode(
            this
        )

        statusHandler.removeCallbacks(statusRunnable)
    }

    // =========================================================
    // KAMERA INDÍTÁSA
    // =========================================================

    private fun startCamera() {

        val previewView =
            findViewById<PreviewView>(
                R.id.cameraPreview
            )

        val cameraProviderFuture =
            ProcessCameraProvider.getInstance(
                this
            )

        cameraProviderFuture.addListener({

            val cameraProvider =
                cameraProviderFuture.get()

            val preview =
                Preview.Builder()
                    .build()
                    .also {

                        it.setSurfaceProvider(
                            previewView.surfaceProvider
                        )
                    }

            imageCapture =
                ImageCapture.Builder()
                    .build()

            val cameraSelector =
                CameraSelector.DEFAULT_FRONT_CAMERA

            try {

                cameraProvider.unbindAll()

                cameraProvider.bindToLifecycle(
                    this,
                    cameraSelector,
                    preview,
                    imageCapture
                )

            } catch (e: Exception) {

                Toast.makeText(
                    this,
                    "Kamera hiba: ${e.message}",
                    Toast.LENGTH_LONG
                ).show()
            }

        }, ContextCompat.getMainExecutor(this))
    }

    // =========================================================
    // FÉNYKÉP KÉSZÍTÉSE
    // =========================================================

    private fun takeEmployeePhoto(
        cardNumber: String,
        onSaved: (String?) -> Unit
    ) {

        val capture =
            imageCapture
                ?: run {

                    onSaved(null)

                    return
                }

        val photoDir =
            File(
                filesDir,
                "photos"
            )

        if (!photoDir.exists()) {
            photoDir.mkdirs()
        }

        val fileName =
            "${System.currentTimeMillis()}_${cardNumber}.jpg"

        val photoFile =
            File(
                photoDir,
                fileName
            )

        val outputOptions =
            ImageCapture.OutputFileOptions
                .Builder(photoFile)
                .build()

        capture.takePicture(
            outputOptions,
            ContextCompat.getMainExecutor(this),
            object :
                ImageCapture.OnImageSavedCallback {

                override fun onImageSaved(
                    outputFileResults:
                    ImageCapture.OutputFileResults
                ) {

                    onSaved(
                        photoFile.absolutePath
                    )
                }

                override fun onError(
                    exception:
                    ImageCaptureException
                ) {

                    onSaved(null)
                }
            }
        )
    }

    // =========================================================
    // GPS
    // =========================================================

    private fun getCurrentLocation(): String {

        if (
            ContextCompat.checkSelfPermission(
                this,
                Manifest.permission.ACCESS_FINE_LOCATION
            ) != PackageManager.PERMISSION_GRANTED
        ) {

            return "GPS engedély nincs"
        }

        val locationManager =
            getSystemService(
                Context.LOCATION_SERVICE
            ) as LocationManager

        val location =
            locationManager.getLastKnownLocation(
                LocationManager.GPS_PROVIDER
            )
                ?: locationManager
                    .getLastKnownLocation(
                        LocationManager.NETWORK_PROVIDER
                    )

        return if (location != null) {

            "${location.latitude}, ${location.longitude}"

        } else {

            "Nincs GPS pozíció"
        }
    }

    // =========================================================
    // NFC KÁRTYA ÉRINTÉS
    // =========================================================

    override fun onTagDiscovered(tag: Tag) {

        val decimal =
            BigInteger(
                1,
                tag.id.reversedArray()
            ).toString()

        val timestamp =
            SimpleDateFormat(
                "yyyy-MM-dd HH:mm:ss",
                Locale.getDefault()
            ).format(
                Date()
            )

        val gps =
            getCurrentLocation()

        /*
         * Az NFC érintés pillanatában rögzítjük a kapcsoló
         * állapotát.
         */
        val selectedEventType =
            if (isArrival) {
                "IN"
            } else {
                "OUT"
            }

        /*
         * Minden blokkolás kap egy globálisan egyedi azonosítót.
         * Ezt használjuk majd a szerveres szinkronnál, hogy
         * ugyanaz az esemény ne kerülhessen fel kétszer.
         */
        val clientEventUuid =
            UUID.randomUUID().toString()

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(
                    this@MainActivity
                )

            val employee =
                db.employeeDao()
                    .findByCardNumber(
                        decimal
                    )

            if (employee == null) {

                runOnUiThread {

                    Toast.makeText(
                        this@MainActivity,
                        "Ismeretlen kártya: $decimal",
                        Toast.LENGTH_LONG
                    ).show()
                }

                return@launch
            }

            val locationManager =
                getSystemService(
                    Context.LOCATION_SERVICE
                ) as LocationManager

            val location =
                if (
                    ContextCompat.checkSelfPermission(
                        this@MainActivity,
                        Manifest.permission.ACCESS_FINE_LOCATION
                    ) ==
                    PackageManager.PERMISSION_GRANTED
                ) {

                    locationManager
                        .getLastKnownLocation(
                            LocationManager.GPS_PROVIDER
                        )
                        ?: locationManager
                            .getLastKnownLocation(
                                LocationManager.NETWORK_PROVIDER
                            )

                } else {

                    null
                }

            takeEmployeePhoto(
                decimal
            ) { photoPath ->

                lifecycleScope.launch {

                    db.eventDao().insert(

                        EventEntity(
                            employeeId = employee.id,
                            cardNumber = decimal,
                            timestamp =
                                System.currentTimeMillis(),
                            latitude =
                                location?.latitude,
                            longitude =
                                location?.longitude,
                            photoPath =
                                photoPath,
                            eventType =
                                selectedEventType,
                            clientEventUuid =
                                clientEventUuid,
                            synced = false
                        )
                    )
                    SyncManager.requestSync(this@MainActivity)
                    updateStatusBar()
                    runOnUiThread {

                        updateDirectionToggle()

                        val intent =
                            Intent(
                                this@MainActivity,
                                BlockSuccessActivity::class.java
                            )

                        intent.putExtra(
                            "employeeName",
                            employee.name
                        )

                        intent.putExtra(
                            "timestamp",
                            timestamp
                        )

                        intent.putExtra(
                            "gps",
                            gps
                        )

                        intent.putExtra(
                            "photoPath",
                            photoPath
                        )

                        intent.putExtra(
                            "eventType",
                            selectedEventType
                        )

                        startActivity(intent)
                    }
                }
            }
        }
    }
}


