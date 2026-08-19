package hu.paksiinformatika.mobilblokkolo

import android.graphics.BitmapFactory
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity

class BlockSuccessActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_block_success)

        val tvEmployee =
            findViewById<TextView>(R.id.tvSuccessEmployee)

        val tvTime =
            findViewById<TextView>(R.id.tvSuccessTime)

        val tvGps =
            findViewById<TextView>(R.id.tvSuccessGps)

        val imgPhoto =
            findViewById<ImageView>(R.id.imgSuccessPhoto)

        val tvSuccessDirection =
            findViewById<TextView>(R.id.tvSuccessDirection)

        val employeeName =
            intent.getStringExtra("employeeName") ?: "-"

        val timestamp =
            intent.getStringExtra("timestamp") ?: "-"

        val gps =
            intent.getStringExtra("gps") ?: "-"

        val photoPath =
            intent.getStringExtra("photoPath")

        val eventType =
            intent.getStringExtra("eventType")

        tvSuccessDirection.text =
            if (eventType == "OUT") {
                "TÁVOZÁS"
            } else {
                "ÉRKEZÉS"
            }

        tvEmployee.text = employeeName
        tvTime.text = timestamp
        tvGps.text = gps

        if (!photoPath.isNullOrEmpty()) {

            val bitmap =
                BitmapFactory.decodeFile(photoPath)

            imgPhoto.setImageBitmap(bitmap)
        }

        // 3 másodperc után vissza a főképernyőre
        Handler(Looper.getMainLooper()).postDelayed({
            finish()
        }, 3000)
    }
}