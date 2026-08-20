package hu.paksiinformatika.mobilblokkolo

import android.os.Bundle
import android.widget.Button
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import hu.paksiinformatika.mobilblokkolo.data.local.EventEntity
import hu.paksiinformatika.mobilblokkolo.data.local.WorkAreaEntity
import kotlinx.coroutines.launch
import kotlin.math.atan2
import kotlin.math.cos
import kotlin.math.sin
import kotlin.math.sqrt

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

            val workAreas =
                db.workAreaDao().getAll()

            for (event in events) {

                val employee =
                    db.employeeDao().findById(event.employeeId)

                items.add(
                    EventListItem(
                        event = event,
                        employeeName = employee?.name ?: "Ismeretlen dolgozó",
                        workAreaName =
                            findWorkAreaName(
                                event,
                                workAreas
                            )
                    )
                )
            }

            recyclerEvents.adapter =
                EventAdapter(items)
        }
    }

    private fun findWorkAreaName(
        event: EventEntity,
        workAreas: List<WorkAreaEntity>
    ): String? {

        val latitude =
            event.latitude

        val longitude =
            event.longitude

        if (
            latitude == null ||
            longitude == null
        ) {
            return null
        }

        return workAreas
            .map { workArea ->
                workArea to distanceMeters(
                    latitude,
                    longitude,
                    workArea.latitude,
                    workArea.longitude
                )
            }
            .filter { pair ->
                pair.second <= pair.first.radiusMeters
            }
            .minByOrNull { pair ->
                pair.second
            }
            ?.first
            ?.name
    }

    private fun distanceMeters(
        lat1: Double,
        lon1: Double,
        lat2: Double,
        lon2: Double
    ): Double {

        val earthRadiusMeters =
            6371000.0

        val latDelta =
            Math.toRadians(lat2 - lat1)

        val lonDelta =
            Math.toRadians(lon2 - lon1)

        val a =
            sin(latDelta / 2) * sin(latDelta / 2) +
                    cos(Math.toRadians(lat1)) *
                    cos(Math.toRadians(lat2)) *
                    sin(lonDelta / 2) *
                    sin(lonDelta / 2)

        return earthRadiusMeters *
                2 *
                atan2(
                    sqrt(a),
                    sqrt(1 - a)
                )
    }
}
