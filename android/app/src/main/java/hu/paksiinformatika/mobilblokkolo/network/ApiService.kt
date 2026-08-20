package hu.paksiinformatika.mobilblokkolo.network

import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.ResponseBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Query
import retrofit2.http.Streaming

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
// ESEMÉNYEK LETÖLTÉSE
// SZERVER -> PDA
// =====================================================

data class EventsResponse(
    val success: Boolean,
    val events: List<EventDto>
)

data class EventDto(
    val id: Long,
    val client_event_uuid: String,
    val employee_id: Long,
    val card_number: String?,
    val event_type: String,
    val event_at: String,
    val latitude: Double?,
    val longitude: Double?,
    val updated_at: String?,
    val deleted_at: String?
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
// PDA SZOFTVERFRISSÍTÉS
// =====================================================

data class SoftwareUpdateCurrentResponse(
    val success: Boolean,
    val update_available: Boolean,
    val update: SoftwareUpdateDto?
)

data class SoftwareUpdateDto(
    val version_code: Int,
    val version_name: String,
    val download_url: String?,
    val sha256: String,
    val file_size: Long,
    val mandatory: Boolean,
    val notes: String?
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
    // BLOKKOLÁSOK LETÖLTÉSE
    // -------------------------------------------------

    @GET("api/events")
    suspend fun getEvents(
        @Header("Authorization")
        authorization: String
    ): EventsResponse

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

    // -------------------------------------------------
    // PDA SZOFTVERFRISSÍTÉS
    // -------------------------------------------------

    @GET("api/software-update/current")
    suspend fun getSoftwareUpdate(
        @Header("Authorization")
        authorization: String,

        @Query("version_code")
        versionCode: Int
    ): SoftwareUpdateCurrentResponse

    @Streaming
    @GET("api/software-update/download")
    suspend fun downloadSoftwareUpdate(
        @Header("Authorization")
        authorization: String
    ): ResponseBody
}
