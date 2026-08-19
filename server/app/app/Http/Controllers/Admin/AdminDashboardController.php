<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceSyncLog;
use App\Models\Employee;
use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'companyCount' => Company::query()->count(),
            'activeCompanyCount' => Company::query()->where('active', true)->count(),
            'employeeCount' => Employee::query()->count(),
            'activeEmployeeCount' => Employee::query()->where('active', true)->count(),
            'cardCount' => Card::query()->count(),
            'activeCardCount' => Card::query()->where('active', true)->count(),
            'deviceCount' => Device::query()->count(),
            'activeDeviceCount' => Device::query()->where('active', true)->count(),
            'eventCount' => Event::query()->count(),
            'photoCount' => EventPhoto::query()->count(),
            'latestEvents' => Event::query()
                ->with(['company', 'device', 'employee', 'card'])
                ->latest('created_at')
                ->limit(10)
                ->get(),
            'latestSyncLogs' => DeviceSyncLog::query()
                ->with(['device.company'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function companies(): View
    {
        return view('admin.companies.index', [
            'companies' => Company::query()
                ->withCount(['employees', 'cards', 'devices', 'events'])
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function employees(): View
    {
        return view('admin.employees.index', [
            'employees' => Employee::query()
                ->with(['company', 'cards'])
                ->orderBy('name')
                ->paginate(50),
        ]);
    }

    public function devices(): View
    {
        return view('admin.devices.index', [
            'devices' => Device::query()
                ->with(['company'])
                ->withCount(['events', 'syncLogs'])
                ->latest('last_seen_at')
                ->paginate(50),
        ]);
    }

    public function events(): View
    {
        return view('admin.events.index', [
            'events' => Event::query()
                ->with(['company', 'device', 'employee', 'card', 'photos'])
                ->latest('created_at')
                ->paginate(50),
        ]);
    }

    public function photos(): View
    {
        return view('admin.photos.index', [
            'photos' => EventPhoto::query()
                ->with(['event.company', 'event.device', 'event.employee'])
                ->latest('uploaded_at')
                ->paginate(50),
        ]);
    }
}
