package hu.paksiinformatika.mobilblokkolo.network

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part

// =====================================================
// DOLGOZÓK LETÖLTÉSE
// =====================================================

data class EmployeesResponse(
    val success: Boolean,
    val company: CompanyDto,
    val employees: List<EmployeeDto>
)

data class CompanyDto(
    val id: Long,
    val name: String,
    val short_name: String?,
    val tax_number: String?,
    val registration_number: String?,
    val email: String?,
    val phone: String?,
    val address: String?,
    val active: Boolean?,
    val updated_at: String?
)

data class EmployeeDto(
    val id: Long,
    val name: String,
    val external_id: String?,
    val active: Boolean,
    val updated_at: String?,
    val cards: List<CardDto>
)

data class CardDto(
    val id: Long,
    val card_number: String,
    val active: Boolean,
    val valid_from: String?,
    val valid_until: String?,
    val updated_at: String?
)

// =====================================================
// ESZKÖZ REGISZTRÁCIÓ
// =====================================================

data class DeviceRegisterRequest(
    val company_id: Long,
    val device_uid: String,
    val name: String?,
    val platform: String = "android",
    val app_version: String?
)

data class DeviceRegisterResponse(
    val success: Boolean,
    val device: DeviceDto,
    val company: CompanyDto,
    val token: String
)

data class DeviceDto(
    val id: Long,
    val device_uid: String,
    val name: String?,
    val company_id: Long,
    val platform: String?,
    val app_version: String?,
    val active: Boolean
)

// =====================================================
// DOLGOZÓ + KÁRTYA SZINKRON
// PDA -> SZERVER
// =====================================================

data class EmployeeSyncRequest(
    val employees: List<EmployeeSyncItem>
)

data class EmployeeSyncItem(
    val id: Long?,
    val name: String,
    val card_number: String,
    val active: Boolean
)

data class EmployeeSyncResponse(
    val success: Boolean,
    val result: EmployeeSyncResult,
    val errors: List<EmployeeSyncError>?,
    val company: CompanyDto?,
    val employees: List<EmployeeDto>?
)

data class EmployeeSyncResult(
    val received: Int,
    val created: Int,
    val updated: Int,
    val server_owned: Int?,
    val failed: Int
)

data class EmployeeSyncError(
    val index: Int?,
    val name: String?,
    val card_number: String?,
    val message: String?
)

// =====================================================
// ESEMÉNY SZINKRON
// PDA -> SZERVER
// =====================================================

data class EventBatchRequest(
    val events: List<EventUploadItem>
)

data class EventUploadItem(
    val client_event_uuid: String,
    val employee_id: Long,
    val card_number: String?,
    val event_type: String,
    val event_at: String,
    val latitude: Double?,
    val longitude: Double?
)

data class EventBatchResponse(
    val success: Boolean,
    val result: EventBatchResult,
    val errors: List<EventBatchError>
)

data class EventBatchResult(
    val received: Int,
    val created: Int,
    val duplicates: Int,
    val failed: Int
)

data class EventBatchError(
    val index: Int?,
    val client_event_uuid: String?,
    val message: String?
)

// =====================================================
// ESEMÉNY FOTÓ FELTÖLTÉS
// =====================================================

data class EventPhotoUploadResponse(
    val success: Boolean,
    val duplicate: Boolean?,
    val photo: EventPhotoDto?,
    val message: String?
)

data class EventPhotoDto(
    val id: Long,
    val event_id: Long,
    val path: String,
    val file_size: Long?,
    val sha256: String?
)

// =====================================================
// API
// =====================================================

interface ApiService {

    // -------------------------------------------------
    // ESZKÖZ REGISZTRÁCIÓ
    // -------------------------------------------------

    @POST("api/device/register")
    suspend fun registerDevice(
        @Body request: DeviceRegisterRequest
    ): DeviceRegisterResponse

    // -------------------------------------------------
    // DOLGOZÓK LETÖLTÉSE
    // -------------------------------------------------

    @GET("api/employees")
    suspend fun getEmployees(
        @Header("Authorization")
        authorization: String
    ): EmployeesResponse

    // -------------------------------------------------
    // DOLGOZÓK + KÁRTYÁK FELTÖLTÉSE
    // -------------------------------------------------

    @POST("api/employees/sync")
    suspend fun syncEmployees(
        @Header("Authorization")
        authorization: String,

        @Body
        request: EmployeeSyncRequest
    ): EmployeeSyncResponse

    // -------------------------------------------------
    // BLOKKOLÁSOK FELTÖLTÉSE
    // -------------------------------------------------

    @POST("api/events/batch")
    suspend fun uploadEvents(
        @Header("Authorization")
        authorization: String,

        @Body
        request: EventBatchRequest
    ): EventBatchResponse

    // -------------------------------------------------
    // BLOKKOLÁSHOZ TARTOZÓ FOTÓ FELTÖLTÉSE
    // -------------------------------------------------

    @Multipart
    @POST("api/events/photo")
    suspend fun uploadEventPhoto(
        @Header("Authorization")
        authorization: String,

        @Part("client_event_uuid")
        clientEventUuid: RequestBody,

        @Part
        photo: MultipartBody.Part
    ): EventPhotoUploadResponse
}
