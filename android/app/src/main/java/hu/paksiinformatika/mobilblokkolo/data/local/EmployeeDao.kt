package hu.paksiinformatika.mobilblokkolo.data.local

import androidx.room3.Dao
import androidx.room3.Query
import androidx.room3.Upsert

@Dao
interface EmployeeDao {

    @Query("""
        SELECT * FROM employees
        WHERE cardNumber = :cardNumber
        AND active = 1
        LIMIT 1
    """)
    suspend fun findByCardNumber(cardNumber: String): EmployeeEntity?

    @Upsert
    suspend fun upsert(employee: EmployeeEntity)

    @Upsert
    suspend fun upsertAll(employees: List<EmployeeEntity>)

    @Query("SELECT * FROM employees ORDER BY name")
    suspend fun getAll(): List<EmployeeEntity>

    @Query("SELECT * FROM employees WHERE active = 1 ORDER BY name")
    suspend fun getActive(): List<EmployeeEntity>

    @Query("""
    SELECT * FROM employees
    WHERE id = :employeeId
    LIMIT 1
""")
    suspend fun findById(employeeId: Long): EmployeeEntity?

    @Query("""
        UPDATE employees
        SET active = 0,
            updatedAt = :updatedAt
        WHERE id NOT IN (:activeServerIds)
    """)
    suspend fun markMissingInactive(
        activeServerIds: List<Long>,
        updatedAt: Long
    )

    @Query("""
        UPDATE employees
        SET active = 0,
            updatedAt = :updatedAt
    """)
    suspend fun markAllInactive(updatedAt: Long)

    @Query("DELETE FROM employees")
    suspend fun deleteAll()
}
