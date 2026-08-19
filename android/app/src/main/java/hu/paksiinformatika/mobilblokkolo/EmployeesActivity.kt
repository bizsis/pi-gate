package hu.paksiinformatika.mobilblokkolo

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import kotlinx.coroutines.launch

class EmployeesActivity : AppCompatActivity() {

    private lateinit var recyclerEmployees: RecyclerView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_employees)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val btnAddEmployee =
            findViewById<Button>(R.id.btnAddEmployee)

        recyclerEmployees =
            findViewById(R.id.recyclerEmployees)

        recyclerEmployees.layoutManager =
            LinearLayoutManager(this)

        btnBack.setOnClickListener {
            finish()
        }

        btnAddEmployee.setOnClickListener {

            startActivity(
                Intent(
                    this,
                    AddEmployeeActivity::class.java
                )
            )
        }
    }

    override fun onResume() {
        super.onResume()

        loadEmployees()
    }

    private fun loadEmployees() {

        lifecycleScope.launch {

            val db =
                DatabaseProvider.getDatabase(
                    this@EmployeesActivity
                )

            val employees =
                db.employeeDao()
                    .getActive()

            recyclerEmployees.adapter =
                EmployeeAdapter(employees)
        }
    }
}
