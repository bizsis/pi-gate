<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AdminWorktimeController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->selectedMonth($request);
        $filters = $this->selectedFilters($request);
        $events = $this->worktimeEvents($request, $month)->get();

        $days = $this->buildDays($month, $events);
        $employeeSummaries = $this->buildEmployeeSummaries($days);
        $filterParams = $this->filterParams($month, $filters);

        return view('admin.worktime.index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'employees' => $this->employeeOptions(),
            'selectedCompanyId' => $filters['company_id'],
            'selectedEmployeeId' => $filters['employee_id'],
            'employeeSearch' => $filters['employee_q'],
            'selectedEmployeeLabel' => $this->selectedEmployeeLabel($filters['employee_id']),
            'filterParams' => $filterParams,
            'month' => $month,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'days' => $days,
            'employeeSummaries' => $employeeSummaries,
            'monthTotalMinutes' => array_sum(array_column($days, 'total_minutes')),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $month = $this->selectedMonth($request);
        $events = $this->worktimeEvents($request, $month)->get();

        $days = $this->buildDays($month, $events);
        $employeeSummaries = $this->buildEmployeeSummaries($days);
        $fileName = 'pi-gate-munkaido-' . $month->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($employeeSummaries): void {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Dolgozó',
                'Cég',
                'Ledolgozott napok',
                'Összes perc',
                'Összes munkaidő',
                'Események',
                'Figyelmeztetések',
            ], ';');

            foreach ($employeeSummaries as $summary) {
                fputcsv($output, [
                    $summary['employee_name'],
                    $summary['company_name'],
                    $summary['worked_days'],
                    $summary['total_minutes'],
                    $this->formatMinutes($summary['total_minutes']),
                    $summary['event_count'],
                    implode(', ', $summary['warnings']),
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function attendanceExport(Request $request): StreamedResponse
    {
        $month = $this->selectedMonth($request);
        $events = $this->worktimeEvents($request, $month)->get();
        $days = $this->buildDays($month, $events);
        $fileName = 'pi-gate-jelenlet-' . $month->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($days): void {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Dátum',
                'Dolgozó',
                'Cég',
                'Első belépés',
                'Utolsó kilépés',
                'Munkaidő',
                'Összes perc',
                'Események',
                'Figyelmeztetések',
            ], ';');

            foreach ($days as $day) {
                foreach ($day['employees'] as $employee) {
                    fputcsv($output, [
                        $day['date'],
                        $employee['employee_name'],
                        $employee['company_name'],
                        $employee['first_in']?->format('H:i:s') ?? '',
                        $employee['last_out']?->format('H:i:s') ?? '',
                        $this->formatMinutes($employee['total_minutes']),
                        $employee['total_minutes'],
                        $employee['event_count'],
                        implode(', ', $employee['warnings']),
                    ], ';');
                }
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function selectedMonth(Request $request): CarbonImmutable
    {
        $month = $request->string('month')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            return CarbonImmutable::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    private function selectedFilters(Request $request): array
    {
        return [
            'company_id' => $request->integer('company_id') ?: null,
            'employee_id' => $request->integer('employee_id') ?: null,
            'employee_q' => trim($request->string('employee_q')->toString()),
        ];
    }

    private function filterParams(CarbonImmutable $month, array $filters): array
    {
        return array_filter([
            'month' => $month->format('Y-m'),
            'company_id' => $filters['company_id'],
            'employee_id' => $filters['employee_id'],
            'employee_q' => $filters['employee_q'],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function worktimeEvents(Request $request, CarbonImmutable $month)
    {
        $filters = $this->selectedFilters($request);

        return Event::query()
            ->with(['company', 'employee'])
            ->when($filters['company_id'], fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($filters['employee_id'], fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when(! $filters['employee_id'] && $filters['employee_q'] !== '', function ($query) use ($filters): void {
                $search = '%' . $filters['employee_q'] . '%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas('employee', fn ($query) => $query->where('name', 'like', $search)->orWhere('external_id', 'like', $search))
                        ->orWhereHas('card', fn ($query) => $query->where('card_number', 'like', $search));
                });
            })
            ->whereBetween('event_at', [
                $month->startOfMonth()->startOfDay(),
                $month->endOfMonth()->endOfDay(),
            ])
            ->orderBy('event_at');
    }

    private function employeeOptions()
    {
        return Employee::query()
            ->with(['company', 'cards'])
            ->orderBy('name')
            ->get();
    }

    private function selectedEmployeeLabel(?int $employeeId): string
    {
        if (! $employeeId) {
            return '';
        }

        $employee = Employee::query()
            ->with(['company', 'cards'])
            ->find($employeeId);

        if (! $employee) {
            return '';
        }

        $label = $employee->name . ' - ' . ($employee->company?->name ?? '-') . ' #' . $employee->id;
        $cards = $employee->cards->pluck('card_number')->implode(', ');

        return $cards ? $label . ' - ' . $cards : $label;
    }

    private function buildDays(CarbonImmutable $month, $events): array
    {
        $eventsByDate = $events->groupBy(fn (Event $event) => $event->event_at->format('Y-m-d'));
        $days = [];

        for ($day = $month->startOfMonth(); $day->lte($month->endOfMonth()); $day = $day->addDay()) {
            $date = $day->format('Y-m-d');
            $employees = [];
            $dayTotalMinutes = 0;

            foreach (($eventsByDate[$date] ?? collect())->groupBy('employee_id') as $employeeEvents) {
                $summary = $this->summarizeEmployeeDay($employeeEvents);
                $dayTotalMinutes += $summary['total_minutes'];
                $employees[] = $summary;
            }

            usort($employees, fn ($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

            $days[] = [
                'date' => $date,
                'day' => $day,
                'is_today' => $day->isToday(),
                'employees' => $employees,
                'total_minutes' => $dayTotalMinutes,
            ];
        }

        return $days;
    }

    private function buildEmployeeSummaries(array $days): array
    {
        $summaries = [];

        foreach ($days as $day) {
            foreach ($day['employees'] as $employee) {
                $key = $employee['company_name'] . '|' . $employee['employee_name'];

                if (! isset($summaries[$key])) {
                    $summaries[$key] = [
                        'employee_name' => $employee['employee_name'],
                        'company_name' => $employee['company_name'],
                        'worked_days' => 0,
                        'total_minutes' => 0,
                        'event_count' => 0,
                        'warnings' => [],
                    ];
                }

                if ($employee['total_minutes'] > 0) {
                    $summaries[$key]['worked_days']++;
                }

                $summaries[$key]['total_minutes'] += $employee['total_minutes'];
                $summaries[$key]['event_count'] += $employee['event_count'];
                $summaries[$key]['warnings'] = array_values(array_unique(array_merge(
                    $summaries[$key]['warnings'],
                    $employee['warnings']
                )));
            }
        }

        uasort($summaries, fn ($a, $b) => [$a['company_name'], $a['employee_name']] <=> [$b['company_name'], $b['employee_name']]);

        return array_values($summaries);
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function summarizeEmployeeDay($events): array
    {
        $employee = $events->first()->employee;
        $company = $events->first()->company;
        $openIn = null;
        $totalMinutes = 0;
        $pairs = [];
        $warnings = [];

        foreach ($events as $event) {
            if ($event->event_type === 'IN') {
                if ($openIn !== null) {
                    $warnings[] = 'Egymás utáni belépés';
                }

                $openIn = $event;
                continue;
            }

            if ($event->event_type === 'OUT') {
                if ($openIn === null) {
                    $warnings[] = 'Kilépés belépés nélkül';
                    continue;
                }

                $minutes = (int) max(0, $openIn->event_at->diffInMinutes($event->event_at));
                $totalMinutes += $minutes;
                $pairs[] = [
                    'in' => $openIn->event_at,
                    'out' => $event->event_at,
                    'minutes' => $minutes,
                ];
                $openIn = null;
            }
        }

        if ($openIn !== null) {
            $warnings[] = 'Nyitott belépés';
        }

        return [
            'employee_id' => $employee?->id,
            'company_id' => $company?->id,
            'employee_name' => $employee?->name ?? 'Ismeretlen dolgozó',
            'company_name' => $company?->name ?? '-',
            'first_in' => $events->firstWhere('event_type', 'IN')?->event_at,
            'last_out' => $events->where('event_type', 'OUT')->last()?->event_at,
            'event_count' => $events->count(),
            'total_minutes' => $totalMinutes,
            'pairs' => $pairs,
            'warnings' => array_unique($warnings),
        ];
    }
}
