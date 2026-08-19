<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCompanyController extends Controller
{
    public function create(): View
    {
        return view('admin.companies.form', [
            'company' => new Company(['active' => true]),
            'title' => 'Új cég',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::create($this->validatedData($request));

        AdminAudit::log($request, 'company.created', $company, null, $company->toArray());

        return redirect()
            ->route('admin.companies')
            ->with('status', 'A cég létrejött.');
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.form', [
            'company' => $company,
            'title' => 'Cég szerkesztése',
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $oldValues = $company->only(array_keys($this->validatedData($request, $company)));
        $company->update($this->validatedData($request, $company));

        AdminAudit::log($request, 'company.updated', $company, $oldValues, $company->fresh()->toArray());

        return redirect()
            ->route('admin.companies')
            ->with('status', 'A cég adatai frissültek.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $oldValues = $company
            ->load(['employees.cards', 'devices'])
            ->toArray();

        DB::transaction(function () use ($company): void {
            $company->employees()->update(['active' => false]);
            $company->cards()->update(['active' => false]);
            $company->devices()->update(['active' => false]);
            $company->update(['active' => false]);
        });

        AdminAudit::log($request, 'company.deleted', $company, $oldValues, $company->fresh()->toArray());

        return redirect()
            ->route('admin.companies')
            ->with('status', 'A cég inaktiválva lett.');
    }

    private function validatedData(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'active' => ['required', Rule::in(['0', '1'])],
        ]);
    }
}
