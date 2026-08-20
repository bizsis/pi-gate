package hu.paksiinformatika.mobilblokkolo

import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageInstaller
import hu.paksiinformatika.mobilblokkolo.network.ApiService
import java.io.File
import java.io.FileInputStream
import java.security.MessageDigest

object SoftwareUpdateManager {

    suspend fun checkDownloadAndInstall(
        context: Context,
        api: ApiService,
        apiToken: String
    ) {

        val prefs =
            context.getSharedPreferences(
                "pi_gate_settings",
                Context.MODE_PRIVATE
            )

        val response =
            api.getSoftwareUpdate(
                authorization =
                    "Bearer $apiToken",

                versionCode =
                    BuildConfig.VERSION_CODE
            )

        if (
            !response.success ||
            !response.update_available ||
            response.update == null
        ) {

            prefs.edit()
                .putBoolean(
                    "software_update_available",
                    false
                )
                .apply()

            return
        }

        val update =
            response.update

        prefs.edit()
            .putBoolean(
                "software_update_available",
                true
            )
            .putInt(
                "software_update_version_code",
                update.version_code
            )
            .putString(
                "software_update_version_name",
                update.version_name
            )
            .apply()

        val updateDir =
            File(
                context.filesDir,
                "updates"
            )

        if (!updateDir.exists()) {
            updateDir.mkdirs()
        }

        val apkFile =
            File(
                updateDir,
                "pi_gate_update_${update.version_code}.apk"
            )

        api.downloadSoftwareUpdate(
            authorization =
                "Bearer $apiToken"
        ).byteStream().use { input ->

            apkFile.outputStream().use { output ->
                input.copyTo(output)
            }
        }

        if (
            apkFile.length() != update.file_size ||
            sha256(apkFile) != update.sha256.lowercase()
        ) {
            prefs.edit()
                .putString(
                    "software_update_last_message",
                    "A letöltött APK ellenőrzése sikertelen."
                )
                .apply()

            return
        }

        installApk(
            context = context,
            apkFile = apkFile
        )
    }

    private fun installApk(
        context: Context,
        apkFile: File
    ) {

        val packageInstaller =
            context.packageManager
                .packageInstaller

        val params =
            PackageInstaller.SessionParams(
                PackageInstaller.SessionParams.MODE_FULL_INSTALL
            ).apply {
                setAppPackageName(
                    context.packageName
                )
            }

        val sessionId =
            packageInstaller.createSession(
                params
            )

        val session =
            packageInstaller.openSession(
                sessionId
            )

        session.openWrite(
            "pi_gate_update",
            0,
            apkFile.length()
        ).use { output ->

            FileInputStream(
                apkFile
            ).use { input ->
                input.copyTo(output)
            }

            session.fsync(
                output
            )
        }

        val intent =
            Intent(
                context,
                SoftwareUpdateInstallReceiver::class.java
            ).setAction(
                "hu.paksiinformatika.mobilblokkolo.SOFTWARE_UPDATE_INSTALLED"
            )

        val pendingIntent =
            PendingIntent.getBroadcast(
                context,
                sessionId,
                intent,
                PendingIntent.FLAG_UPDATE_CURRENT or
                        PendingIntent.FLAG_MUTABLE
            )

        session.commit(
            pendingIntent.intentSender
        )

        session.close()
    }

    private fun sha256(
        file: File
    ): String {

        val digest =
            MessageDigest.getInstance(
                "SHA-256"
            )

        FileInputStream(
            file
        ).use { input ->

            val buffer =
                ByteArray(
                    8192
                )

            while (true) {
                val read =
                    input.read(
                        buffer
                    )

                if (read <= 0) {
                    break
                }

                digest.update(
                    buffer,
                    0,
                    read
                )
            }
        }

        return digest.digest()
            .joinToString("") {
                "%02x".format(it)
            }
    }
}
