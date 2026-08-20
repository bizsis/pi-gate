package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Entity
import androidx.room3.PrimaryKey

@Entity(tableName = "work_areas")
data class WorkAreaEntity(

    @PrimaryKey
    val serverId: Long,

    val companyId: Long,

    val name: String,

    val latitude: Double,

    val longitude: Double,

    val radiusMeters: Int,

    val updatedAt: Long
)
