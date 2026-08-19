<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeSyncController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventPhotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/device/register', [DeviceController::class, 'register']);

Route::middleware('device.auth')->group(function () {

    Route::get('/device/test', function (Request $request) {

        $device = $request->attributes->get('device');

        return response()->json([
            'success' => true,
            'message' => 'Eszköz hitelesítve.',
            'device' => [
                'id' => $device->id,
                'device_uid' => $device->device_uid,
                'name' => $device->name,
                'company_id' => $device->company_id,
            ],
        ]);
    });

    Route::get('/employees', [EmployeeController::class, 'index']);

    Route::post(
        '/employees/sync',
        [EmployeeSyncController::class, 'sync']
    );

    Route::post(
        '/events/batch',
        [EventController::class, 'batch']
    );

    Route::get(
        '/events',
        [EventController::class, 'index']
    );

    Route::post(
        '/events/photo',
        [EventPhotoController::class, 'upload']
    );
});
