package hu.paksiinformatika.mobilblokkolo

import android.app.DatePickerDialog
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale

class WorkTimeActivity : AppCompatActivity() {

    private lateinit var tvSelectedDate: TextView
    private lateinit var recyclerWorkTime: RecyclerView

    private val selectedCalendar: Calendar =
        Calendar.getInstance()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_work_time)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val btnSelectDate =
            findViewById<Button>(R.id.btnSelectDate)

        tvSelectedDate =
            findViewById(R.id.tvSelectedDate)

        recyclerWorkTime =
            findViewById(R.id.recyclerWorkTime)

        btnBack.setOnClickListener {
            finish()
        }

        recyclerWorkTime.layoutManager =
            LinearLayoutManager(this)

        setDefaultDate()

        btnSelectDate.setOnClickListener {
            showDatePicker()
        }

        tvSelectedDate.setOnClickListener {
            showDatePicker()
        }

        loadWorkTime()
    }

    private fun setDefaultDate() {

        val now =
            Calendar.getInstance()

        selectedCalendar.timeInMillis =
            now.timeInMillis

        // 14:00 előtt az előző nap legyen az alapértelmezett
        if (now.get(Calendar.HOUR_OF_DAY) < 14) {
            selectedCalendar.add(
                Calendar.DAY_OF_MONTH,
                -1
            )
        }

        updateDateText()
    }

    private fun showDatePicker() {

        val dialog =
            DatePickerDialog(
                this,
                { _, year, month, dayOfMonth ->

                    selectedCalendar.set(
                        Calendar.YEAR,
                        year
                    )

                    selectedCalendar.set(
                        Calendar.MONTH,
                        month
                    )

                    selectedCalendar.set(
                        Calendar.DAY_OF_MONTH,
                        dayOfMonth
                    )

                    updateDateText()
                    loadWorkTime()
                },
                selectedCalendar.get(Calendar.YEAR),
                selectedCalendar.get(Calendar.MONTH),
                selectedCalendar.get(Calendar.DAY_OF_MONTH)
            )

        dialog.show()
    }

    private fun updateDateText() {

        val formatter =
            SimpleDateFormat(
                "yyyy-MM-dd",
                Locale.getDefault()
            )

        tvSelectedDate.text =
            formatter.format(
                selectedCalendar.time
            )
    }

    private fun loadWorkTime() {

        val startCalendar =
            selectedCalendar.clone() as Calendar

        startCalendar.set(
            Calendar.HOUR_OF_DAY,
            0
        )
        startCalendar.set(
            Calendar.MINUTE,
            0
        )
        startCalendar.set(
            Calendar.SECOND,
            0
        )
        startCalendar.set(
            Calendar.MILLISECOND,
            0
        )

        val endCalendar =
            startCalendar.clone() as Calendar

        endCalendar.add(
            Calendar.DAY_OF_MONTH,
            1
        )

        val startOfDay =
            startCalendar.timeInMillis

        val endOfDay =
            endCalendar.timeInMillis

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(
                    this@WorkTimeActivity
                )

            val employees =
                db.employeeDao().getAll()

            val allEvents =
                db.eventDao().getAll()

            val selectedDayEvents =
                allEvents
                    .filter {
                        it.timestamp >= startOfDay &&
                                it.timestamp < endOfDay
                    }
                    .sortedBy {
                        it.timestamp
                    }

            val timeFormatter =
                SimpleDateFormat(
                    "HH:mm",
                    Locale.getDefault()
                )

            val workItems =
                mutableListOf<WorkTimeItem>()

            for (employee in employees) {

                val employeeEvents =
                    selectedDayEvents
                        .filter {
                            it.employeeId == employee.id
                        }
                        .sortedBy {
                            it.timestamp
                        }

                if (employeeEvents.isEmpty()) {
                    continue
                }

                val eventsBuilder =
                    StringBuilder()

                var workedMilliseconds = 0L
                var openInTimestamp: Long? = null

                for (index in employeeEvents.indices) {

                    val event =
                        employeeEvents[index]

                    val time =
                        timeFormatter.format(
                            Date(event.timestamp)
                        )

                    val eventType =
                        if (event.eventType == "IN") {
                            "BE"
                        } else {
                            "KI"
                        }

                    eventsBuilder
                        .append(time)
                        .append("  ")
                        .append(eventType)
                        .append("\n")

                    when (event.eventType) {

                        "IN" -> {
                            if (openInTimestamp == null) {
                                openInTimestamp = event.timestamp
                            }
                        }

                        "OUT" -> {
                            val inTimestamp = openInTimestamp

                            if (
                                inTimestamp != null &&
                                event.timestamp > inTimestamp
                            ) {
                                workedMilliseconds +=
                                    event.timestamp - inTimestamp

                                openInTimestamp = null
                            }
                        }
                    }
                }

                val totalMinutes =
                    workedMilliseconds / 60000

                val hours =
                    totalMinutes / 60

                val minutes =
                    totalMinutes % 60

                val workedText =
                    "Ledolgozott idő: " +
                            "$hours óra " +
                            String.format(
                                Locale.getDefault(),
                                "%02d",
                                minutes
                            ) +
                            " perc"

                workItems.add(
                    WorkTimeItem(
                        employeeName = employee.name,
                        eventsText =
                            eventsBuilder
                                .toString()
                                .trim(),
                        workedTimeText = workedText
                    )
                )
            }

            recyclerWorkTime.adapter =
                WorkTimeAdapter(workItems)
        }
    }
}