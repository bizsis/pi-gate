<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Company;
use App\Models\Employee;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEmployeeController extends Controller
{
    public function create(): View
    {
        return view('admin.employees.form', [
            'employee' => new Employee(['active' => true]),
            'companies' => $this->companies(),
            'title' => 'Új dolgozó',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $employee = DB::transaction(function () use ($data): Employee {
            $employee = Employee::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'external_id' => $data['external_id'] ?? null,
                'active' => $data['active'],
            ]);

            if (! empty($data['card_number'])) {
                Card::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'card_number' => $data['card_number'],
                    'active' => true,
                ]);
            }

            return $employee;
        });

        AdminAudit::log($request, 'employee.created', $employee, null, $employee->load('cards')->toArray());

        return redirect()
            ->route('admin.employees')
            ->with('status', 'A dolgozó létrejött.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load('cards');

        return view('admin.employees.form', [
            'employee' => $employee,
            'companies' => $this->companies(),
            'title' => 'Dolgozó szerkesztése',
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validatedData($request, $employee);

        $oldValues = $employee->load('cards')->toArray();

        DB::transaction(function () use ($data, $employee): void {
            $employee->update([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'external_id' => $data['external_id'] ?? null,
                'active' => $data['active'],
            ]);

            foreach ($data['cards'] ?? [] as $cardId => $cardData) {
                $card = Card::query()
                    ->where('id', $cardId)
                    ->where('employee_id', $employee->id)
                    ->first();

                if (! $card) {
                    continue;
                }

                $card->update([
                    'company_id' => $employee->company_id,
                    'card_number' => $cardData['card_number'],
                    'active' => $cardData['active'],
                ]);
            }

            if (! empty($data['card_number'])) {
                Card::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'card_number' => $data['card_number'],
                    'active' => true,
                ]);
            }
        });

        AdminAudit::log($request, 'employee.updated', $employee, $oldValues, $employee->fresh()->load('cards')->toArray());

        return redirect()
            ->route('admin.employees')
            ->with('status', 'A dolgozó adatai frissültek.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $oldValues = $employee
            ->load('cards')
            ->toArray();

        DB::transaction(function () use ($employee): void {
            $employee->cards()->update(['active' => false]);
            $employee->update(['active' => false]);
        });

        AdminAudit::log($request, 'employee.deleted', $employee, $oldValues, $employee->fresh()->load('cards')->toArray());

        return redirect()
            ->route('admin.employees')
            ->with('status', 'A dolgozó és a kártyái inaktiválva lettek.');
    }

    private function validatedData(Request $request, ?Employee $employee = null): array
    {
        $companyId = $request->integer('company_id');

        return $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'active' => ['required', Rule::in(['0', '1'])],
            'card_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cards', 'card_number')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'cards' => ['array'],
            'cards.*.card_number' => ['required', 'string', 'max:255'],
            'cards.*.active' => ['required', Rule::in(['0', '1'])],
        ]);
    }

    private function companies()
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
