package hu.paksiinformatika.mobilblokkolo

import android.app.admin.DeviceAdminReceiver
import android.content.Context
import android.content.Intent
import android.widget.Toast

class PiGateDeviceAdminReceiver : DeviceAdminReceiver() {

    override fun onEnabled(context: Context, intent: Intent) {
        Toast.makeText(
            context,
            "PI Gate eszközfelügyelet bekapcsolva.",
            Toast.LENGTH_LONG
        ).show()
    }

    override fun onDisabled(context: Context, intent: Intent) {
        Toast.makeText(
            context,
            "PI Gate eszközfelügyelet kikapcsolva.",
            Toast.LENGTH_LONG
        ).show()
    }
}

