package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.widget.Button
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.launch

class EventLogActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_event_log)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val recyclerEvents =
            findViewById<RecyclerView>(R.id.recyclerEvents)

        btnBack.setOnClickListener {
            finish()
        }

        recyclerEvents.layoutManager =
            LinearLayoutManager(this)

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(this@EventLogActivity)

            val events =
                db.eventDao().getAll()

            val items = mutableListOf<EventListItem>()

            for (event in events) {

                val employee =
                    db.employeeDao().findById(event.employeeId)

                items.add(
                    EventListItem(
                        event = event,
                        employeeName = employee?.name ?: "Ismeretlen dolgozó"
                    )
                )
            }

            recyclerEvents.adapter =
                EventAdapter(items)
        }
    }
}