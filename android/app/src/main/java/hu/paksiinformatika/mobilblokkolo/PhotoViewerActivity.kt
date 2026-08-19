package hu.paksiinformatika.mobilblokkolo

import android.graphics.BitmapFactory
import android.os.Bundle
import android.widget.Button
import android.widget.ImageView
import androidx.appcompat.app.AppCompatActivity

class PhotoViewerActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_photo_viewer)

        val imgFullPhoto =
            findViewById<ImageView>(R.id.imgFullPhoto)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val photoPath =
            intent.getStringExtra("photoPath")

        if (!photoPath.isNullOrEmpty()) {

            val bitmap =
                BitmapFactory.decodeFile(photoPath)

            imgFullPhoto.setImageBitmap(bitmap)
        }

        btnBack.setOnClickListener {
            finish()
        }

        imgFullPhoto.setOnClickListener {
            finish()
        }
    }
}