package hu.paksiinformatika.mobilblokkolo

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.pm.PackageInstaller

class SoftwareUpdateInstallReceiver : BroadcastReceiver() {

    override fun onReceive(
        context: Context,
        intent: Intent
    ) {

        val status =
            intent.getIntExtra(
                PackageInstaller.EXTRA_STATUS,
                PackageInstaller.STATUS_FAILURE
            )

        val prefs =
            context.getSharedPreferences(
                "pi_gate_settings",
                Context.MODE_PRIVATE
            )

        prefs.edit()
            .putLong(
                "software_update_last_result_at",
                System.currentTimeMillis()
            )
            .putInt(
                "software_update_last_status",
                status
            )
            .putString(
                "software_update_last_message",
                intent.getStringExtra(
                    PackageInstaller.EXTRA_STATUS_MESSAGE
                ) ?: ""
            )
            .putBoolean(
                "software_update_available",
                status != PackageInstaller.STATUS_SUCCESS
            )
            .apply()

        if (status == PackageInstaller.STATUS_SUCCESS) {

            val launchIntent =
                context.packageManager
                    .getLaunchIntentForPackage(
                        context.packageName
                    )
                    ?: Intent(
                        context,
                        MainActivity::class.java
                    )

            launchIntent
                .addFlags(
                    Intent.FLAG_ACTIVITY_NEW_TASK or
                            Intent.FLAG_ACTIVITY_CLEAR_TOP
                )

            context.startActivity(
                launchIntent
            )
        }
    }
}
