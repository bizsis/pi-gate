package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Dao
import androidx.room3.Insert
import androidx.room3.Query

@Dao
interface EventDao {

    @Insert
    suspend fun insert(event: EventEntity): Long

    @Query("SELECT * FROM events ORDER BY timestamp DESC")
    suspend fun getAll(): List<EventEntity>

    @Query("SELECT * FROM events WHERE synced = 0 ORDER BY timestamp ASC")
    suspend fun getUnsynced(): List<EventEntity>

    @Query("""
        UPDATE events
        SET employeeId = :serverEmployeeId
        WHERE synced = 0
        AND cardNumber = :cardNumber
    """)
    suspend fun updateUnsyncedEmployeeIdByCardNumber(
        cardNumber: String,
        serverEmployeeId: Long
    )

    @Query("UPDATE events SET synced = 1 WHERE id = :eventId")
    suspend fun markAsSynced(eventId: Long)
}
