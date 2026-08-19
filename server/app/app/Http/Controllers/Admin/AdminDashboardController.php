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
use Illuminate\Http\Request;
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

    public function companies(Request $request): View
    {
        $filters = $request->only(['q', 'active']);

        return view('admin.companies.index', [
            'filters' => $filters,
            'companies' => Company::query()
                ->withCount(['employees', 'cards', 'devices', 'events'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhere('short_name', 'like', $search)
                            ->orWhere('tax_number', 'like', $search)
                            ->orWhere('email', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    });
                })
                ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function employees(Request $request): View
    {
        $filters = $request->only(['q', 'company_id', 'active']);

        return view('admin.employees.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'employees' => Employee::query()
                ->with(['company', 'cards'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhere('external_id', 'like', $search)
                            ->orWhereHas('cards', fn ($query) => $query->where('card_number', 'like', $search));
                    });
                })
                ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function devices(Request $request): View
    {
        $filters = $request->only(['q', 'company_id', 'active']);

        return view('admin.devices.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'devices' => Device::query()
                ->with(['company'])
                ->withCount(['events', 'syncLogs'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhere('device_uid', 'like', $search)
                            ->orWhere('platform', 'like', $search)
                            ->orWhere('app_version', 'like', $search);
                    });
                })
                ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
                ->latest('last_seen_at')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function events(Request $request): View
    {
        $filters = $request->only(['q', 'company_id', 'event_type', 'date_from', 'date_to']);

        return view('admin.events.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'events' => Event::query()
                ->with(['company', 'device', 'employee', 'card', 'photos'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('client_event_uuid', 'like', $search)
                            ->orWhereHas('employee', fn ($query) => $query->where('name', 'like', $search))
                            ->orWhereHas('card', fn ($query) => $query->where('card_number', 'like', $search))
                            ->orWhereHas('device', fn ($query) => $query->where('name', 'like', $search)->orWhere('device_uid', 'like', $search));
                    });
                })
                ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->when(in_array($request->string('event_type')->toString(), ['IN', 'OUT'], true), fn ($query) => $query->where('event_type', $request->string('event_type')->toString()))
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('event_at', '>=', $request->date('date_from')))
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('event_at', '<=', $request->date('date_to')))
                ->latest('created_at')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function photos(Request $request): View
    {
        $filters = $request->only(['q', 'company_id', 'date_from', 'date_to']);

        return view('admin.photos.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'photos' => EventPhoto::query()
                ->with(['event.company', 'event.device', 'event.employee'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('original_name', 'like', $search)
                            ->orWhere('path', 'like', $search)
                            ->orWhere('sha256', 'like', $search)
                            ->orWhereHas('event.employee', fn ($query) => $query->where('name', 'like', $search))
                            ->orWhereHas('event.device', fn ($query) => $query->where('name', 'like', $search)->orWhere('device_uid', 'like', $search));
                    });
                })
                ->when($request->integer('company_id'), fn ($query, $companyId) => $query->whereHas('event', fn ($query) => $query->where('company_id', $companyId)))
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('uploaded_at', '>=', $request->date('date_from')))
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('uploaded_at', '<=', $request->date('date_to')))
                ->latest('uploaded_at')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }
}
