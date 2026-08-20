package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.webkit.WebView
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity

class EventMapActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_event_map)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val tvTitle =
            findViewById<TextView>(R.id.tvMapTitle)

        val tvCoordinates =
            findViewById<TextView>(R.id.tvCoordinates)

        val webMap =
            findViewById<WebView>(R.id.webMap)

        val latitude =
            intent.getDoubleExtra(
                "latitude",
                Double.NaN
            )

        val longitude =
            intent.getDoubleExtra(
                "longitude",
                Double.NaN
            )

        val title =
            intent.getStringExtra("title")
                ?: "Blokkolás helye"

        tvTitle.text =
            title

        tvCoordinates.text =
            "$latitude, $longitude"

        webMap.settings.javaScriptEnabled =
            true

        webMap.settings.domStorageEnabled =
            true

        webMap.loadUrl(
            "https://www.google.com/maps?q=$latitude,$longitude&z=18&output=embed"
        )

        btnBack.setOnClickListener {
            finish()
        }
    }
}
