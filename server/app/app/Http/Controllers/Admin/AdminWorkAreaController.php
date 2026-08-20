<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\WorkArea;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminWorkAreaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'company_id', 'active']);

        return view('admin.work-areas.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'workAreas' => WorkArea::query()
                ->with('company')
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', $search));
                    });
                })
                ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.work-areas.form', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'title' => 'Új munkaterület',
            'workArea' => new WorkArea([
                'company_id' => $request->integer('company_id') ?: null,
                'latitude' => $request->query('latitude'),
                'longitude' => $request->query('longitude'),
                'radius_meters' => 50,
                'active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workArea = WorkArea::create($this->validatedData($request));

        AdminAudit::log($request, 'work_area.created', $workArea, null, $workArea->toArray());

        return redirect()
            ->route('admin.work-areas')
            ->with('status', 'A munkaterület létrejött.');
    }

    public function edit(WorkArea $workArea): View
    {
        return view('admin.work-areas.form', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'title' => 'Munkaterület szerkesztése',
            'workArea' => $workArea,
        ]);
    }

    public function update(Request $request, WorkArea $workArea): RedirectResponse
    {
        $data = $this->validatedData($request);
        $oldValues = $workArea->only(array_keys($data));

        $workArea->update($data);

        AdminAudit::log($request, 'work_area.updated', $workArea, $oldValues, $workArea->fresh()->toArray());

        return redirect()
            ->route('admin.work-areas')
            ->with('status', 'A munkaterület adatai frissültek.');
    }

    public function destroy(Request $request, WorkArea $workArea): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $oldValues = $workArea->toArray();
        $workArea->update(['active' => false]);

        AdminAudit::log($request, 'work_area.deleted', $workArea, $oldValues, $workArea->fresh()->toArray());

        return redirect()
            ->route('admin.work-areas')
            ->with('status', 'A munkaterület inaktiválva lett.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:5000'],
            'active' => ['required', Rule::in(['0', '1'])],
        ]);
    }
}
