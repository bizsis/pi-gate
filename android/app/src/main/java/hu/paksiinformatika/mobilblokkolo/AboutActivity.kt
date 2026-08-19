package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.provider.Settings
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity

class AboutActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_about)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val tvVersion =
            findViewById<TextView>(R.id.tvVersion)

        val tvDeviceId =
            findViewById<TextView>(R.id.tvDeviceId)

        btnBack.setOnClickListener {
            finish()
        }

        tvVersion.text = "Verzió: v1.2.146"

        val androidId =
            Settings.Secure.getString(
                contentResolver,
                Settings.Secure.ANDROID_ID
            )

        tvDeviceId.text =
            "PIGATE-${androidId.uppercase()}"
    }
}