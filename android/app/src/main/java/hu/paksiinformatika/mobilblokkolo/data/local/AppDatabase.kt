package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Database
import androidx.room3.RoomDatabase

@Database(
    entities = [
        EmployeeEntity::class,
        EventEntity::class,
        WorkAreaEntity::class
    ],
    version = 5
)
abstract class AppDatabase : RoomDatabase() {

    abstract fun employeeDao(): EmployeeDao

    abstract fun eventDao(): EventDao

    abstract fun workAreaDao(): WorkAreaDao
}
