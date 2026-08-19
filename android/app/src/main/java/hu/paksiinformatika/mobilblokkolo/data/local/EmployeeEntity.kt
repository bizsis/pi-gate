package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Entity
import androidx.room3.PrimaryKey

@Entity(tableName = "employees")
data class EmployeeEntity(

    @PrimaryKey
    val id: Long,

    val name: String,

    val cardNumber: String,

    val companyId: Long,

    val active: Boolean = true,

    val updatedAt: Long
)