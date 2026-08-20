package hu.paksiinformatika.mobilblokkolo

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import hu.paksiinformatika.mobilblokkolo.data.local.DatabaseProvider
import hu.paksiinformatika.mobilblokkolo.data.local.EmployeeEntity
import hu.paksiinformatika.mobilblokkolo.data.local.EventEntity
import hu.paksiinformatika.mobilblokkolo.data.local.WorkAreaEntity
import hu.paksiinformatika.mobilblokkolo.network.ApiClient
import hu.paksiinformatika.mobilblokkolo.network.CompanyDto
import hu.paksiinformatika.mobilblokkolo.network.EmployeeDto
import hu.paksiinformatika.mobilblokkolo.network.EventDto
import hu.paksiinformatika.mobilblokkolo.network.EmployeeSyncItem
import hu.paksiinformatika.mobilblokkolo.network.EmployeeSyncRequest
import hu.paksiinformatika.mobilblokkolo.network.EventBatchRequest
import hu.paksiinformatika.mobilblokkolo.network.EventUploadItem
import hu.paksiinformatika.mobilblokkolo.network.WorkAreaDto
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.HttpException
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

class SyncWorker(
    appContext: Context,
    workerParams: WorkerParameters
) : CoroutineWorker(
    appContext,
    workerParams
) {

    private data class EmployeeMirrorResult(
        val downloadedNew: Int,
        val downloadedUpdated: Int,
        val activeEmployees: Int
    )

    private data class EventMirrorResult(
        val downloadedNew: Int,
        val downloadedUpdated: Int
    )

    override suspend fun doWork(): Result {

        val prefs =
            applicationContext.getSharedPreferences(
                "pi_gate_settings",
                Context.MODE_PRIVATE
            )

        val serverAddress =
            prefs.getString(
                "server_address",
                ""
            ) ?: ""

        val apiToken =
            prefs.getString(
                "device_api_token",
                ""
            ) ?: ""

        if (
            serverAddress.isBlank() ||
            apiToken.isBlank()
        ) {
            return Result.success()
        }

        return try {

            val db =
                DatabaseProvider.getDatabase(
                    applicationContext
                )

            val api =
                ApiClient.create(
                    applicationContext
                )

            // =====================================================
            // 1. PDA -> SZERVER, MAJD SZERVER -> PDA
            // DOLGOZÓK + KÁRTYÁK + CÉGADAT
            // =====================================================

            val localEmployees =
                db.employeeDao().getAll()

            val uploadEmployeeItems =
                localEmployees
                    .filter { employee ->
                        employee.active ||
                                employee.id < 0L
                    }
                    .map { employee ->

                        EmployeeSyncItem(
                            id =
                                if (employee.id < 0L) {
                                    null
                                } else {
                                    employee.id
                                },
                            name = employee.name,
                            card_number = employee.cardNumber,
                            active = employee.active
                        )
                    }

            val employeeUploadResponse =
                api.syncEmployees(
                    authorization =
                        "Bearer $apiToken",

                    request =
                        EmployeeSyncRequest(
                            employees =
                                uploadEmployeeItems
                        )
                )

            if (!employeeUploadResponse.success) {
                return Result.retry()
            }

            var company =
                employeeUploadResponse.company

            var serverEmployees =
                employeeUploadResponse.employees

            if (
                company == null ||
                serverEmployees == null
            ) {

                val employeesResponse =
                    api.getEmployees(
                        authorization =
                            "Bearer $apiToken"
                    )

                if (!employeesResponse.success) {
                    return Result.retry()
                }

                company =
                    employeesResponse.company

                serverEmployees =
                    employeesResponse.employees
            }

            val downloadedCompany =
                company
                    ?: return Result.retry()

            val downloadedEmployees =
                serverEmployees
                    ?: return Result.retry()

            val employeeMirrorResult =
                applyServerEmployeesToLocalDatabase(
                    db = db,
                    company = downloadedCompany,
                    serverEmployees = downloadedEmployees
                )

            // =====================================================
            // 2. SZERVER -> PDA
            // MUNKATERÜLETEK
            // =====================================================

            val workAreasResponse =
                api.getWorkAreas(
                    authorization =
                        "Bearer $apiToken"
                )

            if (!workAreasResponse.success) {
                return Result.retry()
            }

            val downloadedWorkAreas =
                applyServerWorkAreasToLocalDatabase(
                    db = db,
                    serverWorkAreas =
                        workAreasResponse.work_areas
                )

            // =====================================================
            // 3. PDA -> SZERVER
            // BLOKKOLÁSOK
            // =====================================================

            val unsyncedEvents =
                db.eventDao().getUnsynced()

            var uploadedEvents = 0
            var uploadedPhotos = 0

            if (unsyncedEvents.isNotEmpty()) {

                val eventItems =
                    unsyncedEvents.map { event ->

                        EventUploadItem(
                            client_event_uuid =
                                event.clientEventUuid,

                            employee_id =
                                event.employeeId,

                            card_number =
                                event.cardNumber,

                            event_type =
                                event.eventType,

                            event_at =
                                formatTimestampForServer(
                                    event.timestamp
                                ),

                            latitude =
                                event.latitude,

                            longitude =
                                event.longitude
                        )
                    }

                val eventUploadResponse =
                    api.uploadEvents(
                        authorization =
                            "Bearer $apiToken",

                        request =
                            EventBatchRequest(
                                events =
                                    eventItems
                            )
                    )

                /*
                 * Ha bármely esemény hibás volt,
                 * egyelőre nem jelölünk semmit
                 * szinkronizáltnak.
                 */
                if (
                    eventUploadResponse.result.failed > 0
                ) {
                    return Result.retry()
                }

                // =================================================
                // 3. BLOKKOLÁSOKHOZ TARTOZÓ FOTÓK FELTÖLTÉSE
                // =================================================

                for (event in unsyncedEvents) {

                    val photoPath =
                        event.photoPath

                    /*
                     * Ha nincs fotó az eseményhez,
                     * az esemény önmagában is
                     * szinkronizáltnak tekinthető.
                     */
                    if (photoPath.isNullOrBlank()) {

                        db.eventDao()
                            .markAsSynced(
                                event.id
                            )

                        uploadedEvents++

                        continue
                    }

                    val photoFile =
                        File(photoPath)

                    /*
                     * Ha a helyi fájl valamilyen okból
                     * már nem létezik, az eseményt
                     * nem akarjuk örökre bent tartani
                     * a várakozó listában.
                     *
                     * Az esemény már fent van a szerveren,
                     * ezért szinkronizáltnak jelöljük.
                     */
                    if (!photoFile.exists()) {

                        db.eventDao()
                            .markAsSynced(
                                event.id
                            )

                        uploadedEvents++

                        continue
                    }

                    val clientEventUuidBody =
                        event.clientEventUuid
                            .toRequestBody(
                                "text/plain"
                                    .toMediaType()
                            )

                    val photoRequestBody =
                        photoFile
                            .asRequestBody(
                                "image/jpeg"
                                    .toMediaType()
                            )

                    val photoPart =
                        MultipartBody.Part
                            .createFormData(
                                "photo",
                                photoFile.name,
                                photoRequestBody
                            )

                    val photoResponse =
                        api.uploadEventPhoto(
                            authorization =
                                "Bearer $apiToken",

                            clientEventUuid =
                                clientEventUuidBody,

                            photo =
                                photoPart
                        )

                    if (!photoResponse.success) {

                        /*
                         * Az esemény már felkerült,
                         * de a fotó még nem.
                         *
                         * Nem jelöljük synced=true-ra,
                         * így a következő futás újrapróbálja.
                         */
                        return Result.retry()
                    }

                    uploadedPhotos++

                    /*
                     * Csak akkor tekintjük teljesen
                     * szinkronizáltnak, ha az esemény
                     * és a fotó is felkerült.
                     */
                    db.eventDao()
                        .markAsSynced(
                            event.id
                        )

                    uploadedEvents++
                }
            }

            // =====================================================
            // 4. SZERVER -> PDA
            // BLOKKOLÁSOK
            // =====================================================

            val eventDownloadResponse =
                api.getEvents(
                    authorization =
                        "Bearer $apiToken"
                )

            if (!eventDownloadResponse.success) {
                return Result.retry()
            }

            val eventMirrorResult =
                applyServerEventsToLocalDatabase(
                    db = db,
                    serverEvents =
                        eventDownloadResponse.events
                )

            // =====================================================
            // 5. SZINKRON ÁLLAPOT MENTÉSE
            // =====================================================

            val syncTime =
                System.currentTimeMillis()

            prefs.edit()

                .putLong(
                    "last_employee_sync",
                    syncTime
                )

                .putLong(
                    "last_auto_sync",
                    syncTime
                )

                .putString(
                    "company_name",
                    downloadedCompany.name
                )

                .putLong(
                    "company_id",
                    downloadedCompany.id
                )

                .putString(
                    "company_short_name",
                    downloadedCompany.short_name ?: ""
                )

                .putString(
                    "company_tax_number",
                    downloadedCompany.tax_number ?: ""
                )

                .putString(
                    "company_registration_number",
                    downloadedCompany.registration_number ?: ""
                )

                .putString(
                    "company_email",
                    downloadedCompany.email ?: ""
                )

                .putString(
                    "company_phone",
                    downloadedCompany.phone ?: ""
                )

                .putString(
                    "company_address",
                    downloadedCompany.address ?: ""
                )

                .putBoolean(
                    "company_active",
                    downloadedCompany.active ?: true
                )

                .putInt(
                    "last_sync_uploaded_new",
                    employeeUploadResponse
                        .result
                        .created
                )

                .putInt(
                    "last_sync_uploaded_updated",
                    employeeUploadResponse
                        .result
                        .updated
                )

                .putInt(
                    "last_sync_downloaded_new",
                    employeeMirrorResult
                        .downloadedNew
                )

                .putInt(
                    "last_sync_downloaded_updated",
                    employeeMirrorResult
                        .downloadedUpdated
                )

                .putInt(
                    "last_sync_active_employees",
                    employeeMirrorResult
                        .activeEmployees
                )

                .putInt(
                    "last_sync_uploaded_events",
                    uploadedEvents
                )

                .putInt(
                    "last_sync_uploaded_photos",
                    uploadedPhotos
                )

                .putInt(
                    "last_sync_downloaded_events_new",
                    eventMirrorResult
                        .downloadedNew
                )

                .putInt(
                    "last_sync_downloaded_events_updated",
                    eventMirrorResult
                        .downloadedUpdated
                )

                .putInt(
                    "last_sync_downloaded_work_areas",
                    downloadedWorkAreas
                )

                .apply()

            try {
                SoftwareUpdateManager.checkDownloadAndInstall(
                    context =
                        applicationContext,

                    api =
                        api,

                    apiToken =
                        apiToken
                )
            } catch (e: Exception) {
                prefs.edit()
                    .putString(
                        "software_update_last_message",
                        e.message ?: ""
                    )
                    .apply()
            }

            Result.success()

        } catch (e: HttpException) {

            if (
                e.code() == 401 ||
                e.code() == 403
            ) {

                Result.failure()

            } else {

                Result.retry()
            }

        } catch (e: Exception) {

            Result.retry()
        }
    }

    // =========================================================
    // SZERVER OLDALI MUNKATERÜLETEK TÜKRÖZÉSE PDA-RA
    // =========================================================

    private suspend fun applyServerWorkAreasToLocalDatabase(
        db: hu.paksiinformatika.mobilblokkolo.data.local.AppDatabase,
        serverWorkAreas: List<WorkAreaDto>
    ): Int {

        val now =
            System.currentTimeMillis()

        val localWorkAreas =
            serverWorkAreas.map { workArea ->
                WorkAreaEntity(
                    serverId =
                        workArea.id,

                    companyId =
                        workArea.company_id,

                    name =
                        workArea.name,

                    latitude =
                        workArea.latitude,

                    longitude =
                        workArea.longitude,

                    radiusMeters =
                        workArea.radius_meters,

                    updatedAt =
                        parseServerTimestamp(
                            workArea.updated_at
                        ) ?: now
                )
            }

        db.workAreaDao()
            .deleteAll()

        db.workAreaDao()
            .upsertAll(
                localWorkAreas
            )

        return localWorkAreas.size
    }

    // =========================================================
    // IDŐPONT FORMÁZÁSA A SZERVERNEK
    // =========================================================

    private fun formatTimestampForServer(
        timestamp: Long
    ): String {

        val formatter =
            SimpleDateFormat(
                "yyyy-MM-dd'T'HH:mm:ssXXX",
                Locale.US
            )

        formatter.timeZone =
            TimeZone.getDefault()

        return formatter.format(
            Date(timestamp)
        )
    }

    // =========================================================
    // SZERVER OLDALI DOLGOZÓTÖRZS TÜKRÖZÉSE PDA-RA
    // =========================================================

    private suspend fun applyServerEmployeesToLocalDatabase(
        db: hu.paksiinformatika.mobilblokkolo.data.local.AppDatabase,
        company: CompanyDto,
        serverEmployees: List<EmployeeDto>
    ): EmployeeMirrorResult {

        val now =
            System.currentTimeMillis()

        val currentEmployees =
            db.employeeDao()
                .getAll()

        val currentById =
            currentEmployees
                .associateBy {
                    it.id
                }

        val serverMirrorEmployees =
            serverEmployees
                .mapNotNull { serverEmployee ->

                    val activeCard =
                        serverEmployee.cards
                            .firstOrNull {
                                it.active
                            }
                            ?: return@mapNotNull null

                    EmployeeEntity(
                        id =
                            serverEmployee.id,

                        name =
                            serverEmployee.name,

                        cardNumber =
                            activeCard.card_number,

                        companyId =
                            company.id,

                        active =
                            serverEmployee.active,

                        updatedAt =
                            parseServerTimestamp(
                                serverEmployee.updated_at
                            ) ?: parseServerTimestamp(
                                activeCard.updated_at
                            ) ?: now
                    )
                }

        var downloadedNew = 0
        var downloadedUpdated = 0

        for (serverEmployee in serverMirrorEmployees) {

            val existingEmployee =
                currentById[serverEmployee.id]

            if (existingEmployee == null) {

                downloadedNew++

            } else {

                val changed =
                    existingEmployee.name !=
                            serverEmployee.name ||
                            existingEmployee.cardNumber !=
                            serverEmployee.cardNumber ||
                            existingEmployee.companyId !=
                            serverEmployee.companyId ||
                            existingEmployee.active !=
                            serverEmployee.active

                if (changed) {
                    downloadedUpdated++
                }
            }
        }

        db.employeeDao()
            .upsertAll(
                serverMirrorEmployees
            )

        for (serverEmployee in serverMirrorEmployees) {

            db.eventDao()
                .updateUnsyncedEmployeeIdByCardNumber(
                    cardNumber =
                        serverEmployee.cardNumber,

                    serverEmployeeId =
                        serverEmployee.id
                )
        }

        val activeServerIds =
            serverMirrorEmployees
                .map {
                    it.id
                }

        if (activeServerIds.isEmpty()) {

            db.employeeDao()
                .markAllInactive(
                    now
                )

        } else {

            db.employeeDao()
                .markMissingInactive(
                    activeServerIds,
                    now
                )
        }

        return EmployeeMirrorResult(
            downloadedNew =
                downloadedNew,

            downloadedUpdated =
                downloadedUpdated,

            activeEmployees =
                serverMirrorEmployees.size
        )
    }

    // =========================================================
    // SZERVER OLDALI BLOKKOLÁSOK TÜKRÖZÉSE PDA-RA
    // =========================================================

    private suspend fun applyServerEventsToLocalDatabase(
        db: hu.paksiinformatika.mobilblokkolo.data.local.AppDatabase,
        serverEvents: List<EventDto>
    ): EventMirrorResult {

        var downloadedNew = 0
        var downloadedUpdated = 0

        for (serverEvent in serverEvents) {

            if (serverEvent.deleted_at != null) {

                val deletedRows =
                    db.eventDao()
                        .deleteSyncedByClientEventUuid(
                            serverEvent.client_event_uuid
                        )

                if (deletedRows > 0) {
                    downloadedUpdated++
                }

                continue
            }

            val eventTimestamp =
                parseServerTimestamp(
                    serverEvent.event_at
                ) ?: continue

            val cardNumber =
                serverEvent.card_number ?: ""

            val existingEvent =
                db.eventDao()
                    .findByClientEventUuid(
                        serverEvent.client_event_uuid
                    )

            if (existingEvent == null) {

                db.eventDao()
                    .insert(
                        EventEntity(
                            employeeId =
                                serverEvent.employee_id,

                            cardNumber =
                                cardNumber,

                            timestamp =
                                eventTimestamp,

                            latitude =
                                serverEvent.latitude,

                            longitude =
                                serverEvent.longitude,

                            photoPath =
                                null,

                            eventType =
                                serverEvent.event_type,

                            clientEventUuid =
                                serverEvent.client_event_uuid,

                            synced =
                                true
                        )
                    )

                downloadedNew++

                continue
            }

            if (!existingEvent.synced) {
                continue
            }

            val changed =
                existingEvent.employeeId !=
                        serverEvent.employee_id ||
                        existingEvent.cardNumber !=
                        cardNumber ||
                        existingEvent.timestamp !=
                        eventTimestamp ||
                        existingEvent.latitude !=
                        serverEvent.latitude ||
                        existingEvent.longitude !=
                        serverEvent.longitude ||
                        existingEvent.eventType !=
                        serverEvent.event_type

            if (!changed) {
                continue
            }

            val affectedRows =
                db.eventDao()
                    .updateSyncedFromServer(
                        clientEventUuid =
                            serverEvent.client_event_uuid,

                        employeeId =
                            serverEvent.employee_id,

                        cardNumber =
                            cardNumber,

                        timestamp =
                            eventTimestamp,

                        latitude =
                            serverEvent.latitude,

                        longitude =
                            serverEvent.longitude,

                        eventType =
                            serverEvent.event_type
                    )

            if (affectedRows > 0) {
                downloadedUpdated++
            }
        }

        return EventMirrorResult(
            downloadedNew =
                downloadedNew,

            downloadedUpdated =
                downloadedUpdated
        )
    }

    private fun parseServerTimestamp(
        value: String?
    ): Long? {

        if (value.isNullOrBlank()) {
            return null
        }

        val normalizedUtc =
            value.replace(
                Regex(
                    "\\.(\\d{3})\\d*Z$"
                ),
                ".$1Z"
            )

        val patterns =
            listOf(
                "yyyy-MM-dd'T'HH:mm:ss.SSS'Z'",
                "yyyy-MM-dd'T'HH:mm:ss'Z'",
                "yyyy-MM-dd'T'HH:mm:ss.SSSXXX",
                "yyyy-MM-dd'T'HH:mm:ssXXX",
                "yyyy-MM-dd HH:mm:ss"
            )

        for (pattern in patterns) {

            try {

                val formatter =
                    SimpleDateFormat(
                        pattern,
                        Locale.US
                    )

                if (pattern.endsWith("'Z'")) {
                    formatter.timeZone =
                        TimeZone.getTimeZone(
                            "UTC"
                        )
                }

                return formatter.parse(
                    normalizedUtc
                )?.time

            } catch (e: Exception) {

                continue
            }
        }

        return null
    }
}
