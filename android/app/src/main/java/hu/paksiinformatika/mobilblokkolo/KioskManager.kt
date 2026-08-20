package hu.paksiinformatika.mobilblokkolo

import android.app.Activity
import android.app.admin.DevicePolicyManager
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.UserManager
import android.provider.Settings
import android.widget.Toast

object KioskManager {

    private const val SETTINGS_PACKAGE = "com.android.settings"

    fun adminComponent(context: Context): ComponentName {
        return ComponentName(
            context,
            PiGateDeviceAdminReceiver::class.java
        )
    }

    fun isDeviceOwner(context: Context): Boolean {
        val dpm =
            context.getSystemService(Context.DEVICE_POLICY_SERVICE)
                    as DevicePolicyManager

        return dpm.isDeviceOwnerApp(context.packageName)
    }

    fun configureDeviceOwnerPolicies(context: Context) {
        if (!isDeviceOwner(context)) {
            return
        }

        val dpm =
            context.getSystemService(Context.DEVICE_POLICY_SERVICE)
                    as DevicePolicyManager

        val admin =
            adminComponent(context)

        dpm.setLockTaskPackages(
            admin,
            arrayOf(
                context.packageName,
                SETTINGS_PACKAGE
            )
        )

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            dpm.setLockTaskFeatures(
                admin,
                DevicePolicyManager.LOCK_TASK_FEATURE_NONE
            )
        }

        dpm.setKeyguardDisabled(admin, true)

        val homeFilter =
            android.content.IntentFilter(Intent.ACTION_MAIN).apply {
                addCategory(Intent.CATEGORY_HOME)
                addCategory(Intent.CATEGORY_DEFAULT)
            }

        dpm.addPersistentPreferredActivity(
            admin,
            homeFilter,
            ComponentName(context, MainActivity::class.java)
        )

        listOf(
            UserManager.DISALLOW_ADD_USER,
            UserManager.DISALLOW_SAFE_BOOT,
            UserManager.DISALLOW_FACTORY_RESET,
            UserManager.DISALLOW_MOUNT_PHYSICAL_MEDIA,
            UserManager.DISALLOW_INSTALL_UNKNOWN_SOURCES,
            UserManager.DISALLOW_INSTALL_UNKNOWN_SOURCES_GLOBALLY
        ).forEach { restriction ->
            dpm.addUserRestriction(admin, restriction)
        }
    }

    fun startKioskIfOwner(activity: Activity) {
        if (!isDeviceOwner(activity)) {
            return
        }

        configureDeviceOwnerPolicies(activity)

        try {
            activity.startLockTask()
        } catch (e: IllegalStateException) {
            Toast.makeText(
                activity,
                "A kiosk mód nem indítható: ${e.message}",
                Toast.LENGTH_LONG
            ).show()
        }
    }

    fun stopKiosk(activity: Activity) {
        try {
            activity.stopLockTask()
        } catch (_: IllegalStateException) {
            // Nincs aktív lock task.
        }
    }

    fun unlockForAdminExit(activity: Activity) {
        stopKiosk(activity)

        if (isDeviceOwner(activity)) {
            val dpm =
                activity.getSystemService(Context.DEVICE_POLICY_SERVICE)
                        as DevicePolicyManager

            dpm.clearPackagePersistentPreferredActivities(
                adminComponent(activity),
                activity.packageName
            )
        }
    }

    fun openWifiSettings(activity: Activity) {
        val intent =
            Intent(Settings.ACTION_WIFI_SETTINGS).apply {
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }

        activity.startActivity(intent)
    }
}
