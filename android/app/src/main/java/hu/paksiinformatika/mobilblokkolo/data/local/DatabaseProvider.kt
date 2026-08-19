package hu.paksiinformatika.mobilblokkolo.data.local

import android.content.Context
import androidx.room3.Room

object DatabaseProvider {

    @Volatile
    private var INSTANCE: AppDatabase? = null

    fun getDatabase(context: Context): AppDatabase {

        return INSTANCE ?: synchronized(this) {

            val instance =
                Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    "pi_gate_database"
                )
                    .build()

            INSTANCE = instance

            instance
        }
    }
}