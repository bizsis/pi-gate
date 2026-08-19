package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Database
import androidx.room3.RoomDatabase

@Database(
    entities = [
        EmployeeEntity::class,
        EventEntity::class
    ],
    version = 4
)
abstract class AppDatabase : RoomDatabase() {

    abstract fun employeeDao(): EmployeeDao

    abstract fun eventDao(): EventDao
}