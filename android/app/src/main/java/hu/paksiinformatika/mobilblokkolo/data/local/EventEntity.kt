package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Entity
import androidx.room3.PrimaryKey

@Entity(tableName = "events")
data class EventEntity(

    @PrimaryKey(autoGenerate = true)
    val id: Long = 0,

    val employeeId: Long,

    val cardNumber: String,

    val timestamp: Long,

    val latitude: Double?,

    val longitude: Double?,

    val photoPath: String? = null,

    // IN = ÉRKEZÉS
    // OUT = TÁVOZÁS
    val eventType: String,
    val clientEventUuid: String,
    val synced: Boolean = false
)