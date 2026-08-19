package hu.paksiinformatika.mobilblokkolo

import android.nfc.NfcAdapter
import android.nfc.Tag
import android.os.Bundle
import android.widget.Button
import android.widget.Switch
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.textfield.TextInputEditText
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import hu.paksiinformatika.mobilblokkolo.data.local.EmployeeEntity
import kotlinx.coroutines.launch
import java.math.BigInteger

class AddEmployeeActivity :
    AppCompatActivity(),
    NfcAdapter.ReaderCallback {

    private var nfcAdapter: NfcAdapter? = null

    private lateinit var etEmployeeName: TextInputEditText
    private lateinit var etCardNumber: TextInputEditText
    private lateinit var switchActive: Switch

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_add_employee)

        nfcAdapter =
            NfcAdapter.getDefaultAdapter(this)

        val btnBack =
            findViewById<Button>(R.id.btnBack)

        val btnSaveEmployee =
            findViewById<Button>(R.id.btnSaveEmployee)

        etEmployeeName =
            findViewById(R.id.etEmployeeName)

        etCardNumber =
            findViewById(R.id.etCardNumber)

        switchActive =
            findViewById(R.id.switchActive)

        btnBack.setOnClickListener {
            finish()
        }

        btnSaveEmployee.setOnClickListener {

            val name =
                etEmployeeName.text
                    ?.toString()
                    ?.trim()
                    ?: ""

            val cardNumber =
                etCardNumber.text
                    ?.toString()
                    ?.trim()
                    ?: ""

            if (name.isBlank()) {

                Toast.makeText(
                    this,
                    "Add meg a dolgozó nevét.",
                    Toast.LENGTH_LONG
                ).show()

                return@setOnClickListener
            }

            if (cardNumber.isBlank()) {

                Toast.makeText(
                    this,
                    "Add meg vagy olvasd be a kártyát.",
                    Toast.LENGTH_LONG
                ).show()

                return@setOnClickListener
            }

            lifecycleScope.launch {

                val db =
                    DatabaseProvider.getDatabase(
                        this@AddEmployeeActivity
                    )

                val existingEmployee =
                    db.employeeDao()
                        .findByCardNumber(cardNumber)

                if (existingEmployee != null) {

                    Toast.makeText(
                        this@AddEmployeeActivity,
                        "Ez a kártya már hozzá van rendelve: ${existingEmployee.name}",
                        Toast.LENGTH_LONG
                    ).show()

                    return@launch
                }

                val employees =
                    db.employeeDao().getAll()

                val nextId =
                    (employees
                        .filter {
                            it.id < 0L
                        }
                        .minOfOrNull {
                            it.id
                        } ?: 0L) - 1L

                val prefs =
                    getSharedPreferences(
                        "pi_gate_settings",
                        MODE_PRIVATE
                    )

                val companyId =
                    prefs.getLong(
                        "company_id",
                        1L
                    )

                db.employeeDao().upsert(
                    EmployeeEntity(
                        id = nextId,
                        name = name,
                        cardNumber = cardNumber,
                        companyId = companyId,
                        active = switchActive.isChecked,
                        updatedAt = System.currentTimeMillis()
                    )
                )
                SyncManager.requestSync(this@AddEmployeeActivity)
                Toast.makeText(
                    this@AddEmployeeActivity,
                    "Dolgozó elmentve.",
                    Toast.LENGTH_LONG
                ).show()

                finish()
            }
        }
    }

    override fun onResume() {
        super.onResume()

        nfcAdapter?.enableReaderMode(
            this,
            this,
            NfcAdapter.FLAG_READER_NFC_A or
                    NfcAdapter.FLAG_READER_SKIP_NDEF_CHECK,
            null
        )
    }

    override fun onPause() {
        super.onPause()

        nfcAdapter?.disableReaderMode(this)
    }

    override fun onTagDiscovered(tag: Tag) {

        val cardNumber =
            BigInteger(
                1,
                tag.id.reversedArray()
            ).toString()

        runOnUiThread {

            etCardNumber.setText(cardNumber)

            Toast.makeText(
                this,
                "Kártya beolvasva: $cardNumber",
                Toast.LENGTH_SHORT
            ).show()
        }
    }
}
