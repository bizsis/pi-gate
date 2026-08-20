package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Dao
import androidx.room3.Insert
import androidx.room3.OnConflictStrategy
import androidx.room3.Query

@Dao
interface WorkAreaDao {

    @Query("SELECT * FROM work_areas ORDER BY name")
    suspend fun getAll(): List<WorkAreaEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(workAreas: List<WorkAreaEntity>)

    @Query("DELETE FROM work_areas")
    suspend fun deleteAll()
}
