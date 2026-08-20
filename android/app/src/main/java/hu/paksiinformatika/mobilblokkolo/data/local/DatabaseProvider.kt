package hu.paksiinformatika.mobilblokkolo.data.local

import android.content.Context
import androidx.room3.Room
import androidx.room3.migration.Migration
import androidx.sqlite.SQLiteConnection

object DatabaseProvider {

    private val MIGRATION_4_5 =
        object : Migration(4, 5) {

            override suspend fun migrate(
                connection: SQLiteConnection
            ) {
                connection.prepare(
                    sql =
                        """
                    CREATE TABLE IF NOT EXISTS work_areas (
                        serverId INTEGER NOT NULL PRIMARY KEY,
                        companyId INTEGER NOT NULL,
                        name TEXT NOT NULL,
                        latitude REAL NOT NULL,
                        longitude REAL NOT NULL,
                        radiusMeters INTEGER NOT NULL,
                        updatedAt INTEGER NOT NULL
                    )
                    """.trimIndent()
                ).use { statement ->
                    statement.step()
                }
            }
        }

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
                    .addMigrations(
                        MIGRATION_4_5
                    )
                    .build()

            INSTANCE = instance

            instance
        }
    }
}
