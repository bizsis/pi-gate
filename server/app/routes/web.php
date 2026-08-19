<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminDeviceController;
use App\Http\Controllers\Admin\AdminEmployeeController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminPhotoController;
use App\Http\Controllers\Admin\AdminWorktimeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])
        ->name('admin.login');

    Route::post('/admin/login', [AdminAuthController::class, 'login'])
        ->name('admin.login.submit');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])
        ->name('logout');

    Route::get('/', [AdminDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/companies', [AdminDashboardController::class, 'companies'])
        ->name('companies');

    Route::get('/companies/create', [AdminCompanyController::class, 'create'])
        ->name('companies.create');

    Route::post('/companies', [AdminCompanyController::class, 'store'])
        ->name('companies.store');

    Route::get('/companies/{company}/edit', [AdminCompanyController::class, 'edit'])
        ->name('companies.edit');

    Route::put('/companies/{company}', [AdminCompanyController::class, 'update'])
        ->name('companies.update');

    Route::get('/employees', [AdminDashboardController::class, 'employees'])
        ->name('employees');

    Route::get('/employees/create', [AdminEmployeeController::class, 'create'])
        ->name('employees.create');

    Route::post('/employees', [AdminEmployeeController::class, 'store'])
        ->name('employees.store');

    Route::get('/employees/{employee}/edit', [AdminEmployeeController::class, 'edit'])
        ->name('employees.edit');

    Route::put('/employees/{employee}', [AdminEmployeeController::class, 'update'])
        ->name('employees.update');

    Route::get('/devices', [AdminDashboardController::class, 'devices'])
        ->name('devices');

    Route::get('/devices/{device}/edit', [AdminDeviceController::class, 'edit'])
        ->name('devices.edit');

    Route::put('/devices/{device}', [AdminDeviceController::class, 'update'])
        ->name('devices.update');

    Route::get('/events', [AdminDashboardController::class, 'events'])
        ->name('events');

    Route::get('/events/{event}/edit', [AdminEventController::class, 'edit'])
        ->name('events.edit');

    Route::put('/events/{event}', [AdminEventController::class, 'update'])
        ->name('events.update');

    Route::get('/worktime', [AdminWorktimeController::class, 'index'])
        ->name('worktime');

    Route::get('/worktime/export', [AdminWorktimeController::class, 'export'])
        ->name('worktime.export');

    Route::get('/photos', [AdminDashboardController::class, 'photos'])
        ->name('photos');

    Route::get('/photos/{photo}', [AdminPhotoController::class, 'show'])
        ->name('photos.show');

    Route::get('/logs', [AdminLogController::class, 'index'])
        ->name('logs');
});
