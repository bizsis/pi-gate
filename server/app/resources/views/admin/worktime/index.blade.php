@extends('admin.layout', ['title' => 'Munkaidő - PI Gate Admin'])

@php
    $weekdays = ['H', 'K', 'Sze', 'Cs', 'P', 'Szo', 'V'];
    $calendarOffset = (int) $month->startOfMonth()->isoWeekday() - 1;
    $formatMinutes = function (int $minutes): string {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    };
@endphp

@section('body')
    <div class="page-head">
        <div>
            <h1>Munkaidő</h1>
            <div class="muted">Havi naptár az IN/OUT blokkolások alapján</div>
        </div>
        <div class="cards-list">
            <a class="action secondary" href="{{ route('admin.worktime', ['month' => $previousMonth, 'company_id' => $selectedCompanyId]) }}">Előző hónap</a>
            <a class="action secondary" href="{{ route('admin.worktime', ['month' => $nextMonth, 'company_id' => $selectedCompanyId]) }}">Következő hónap</a>
            <a class="action" href="{{ route('admin.worktime.export', ['month' => $month->format('Y-m'), 'company_id' => $selectedCompanyId]) }}">CSV export</a>
        </div>
    </div>

    <form class="panel" method="get" action="{{ route('admin.worktime') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="month">Hónap</label>
                <input id="month" name="month" type="month" value="{{ $month->format('Y-m') }}">
            </div>
            <div class="form-row">
                <label for="company_id">Cég</label>
                <select id="company_id" name="company_id">
                    <option value="">Minden cég</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="grid">
        <div class="stat">
            <div class="value">{{ $month->format('Y.m') }}</div>
            <div class="label">Megjelenített hónap</div>
        </div>
        <div class="stat">
            <div class="value">{{ $formatMinutes($monthTotalMinutes) }}</div>
            <div class="label">Összes számolt munkaidő</div>
        </div>
        <div class="stat">
            <div class="value">{{ count($employeeSummaries) }}</div>
            <div class="label">Dolgozó a havi összesítőben</div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">Havi összesítő</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Dolgozó</th>
                        <th>Cég</th>
                        <th>Ledolgozott napok</th>
                        <th>Munkaidő</th>
                        <th>Összes perc</th>
                        <th>Események</th>
                        <th>Figyelmeztetések</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employeeSummaries as $summary)
                        <tr>
                            <td>{{ $summary['employee_name'] }}</td>
                            <td>{{ $summary['company_name'] }}</td>
                            <td>{{ $summary['worked_days'] }}</td>
                            <td><strong>{{ $formatMinutes($summary['total_minutes']) }}</strong></td>
                            <td>{{ $summary['total_minutes'] }}</td>
                            <td>{{ $summary['event_count'] }}</td>
                            <td>
                                @forelse ($summary['warnings'] as $warning)
                                    <span class="badge warn">{{ $warning }}</span>
                                @empty
                                    <span class="badge ok">Rendben</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Nincs munkaidő adat erre a hónapra.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">Naptár</div>
        <div class="work-calendar">
            @foreach ($weekdays as $weekday)
                <div class="calendar-head">{{ $weekday }}</div>
            @endforeach

            @for ($i = 0; $i < $calendarOffset; $i++)
                <div class="calendar-day calendar-empty"></div>
            @endfor

            @foreach ($days as $day)
                <div class="calendar-day {{ $day['is_today'] ? 'today' : '' }}">
                    <div class="calendar-date">
                        <strong>{{ $day['day']->format('j') }}</strong>
                        <span>{{ $formatMinutes($day['total_minutes']) }}</span>
                    </div>

                    @forelse ($day['employees'] as $employee)
                        <details class="work-entry">
                            <summary>
                                <span>{{ $employee['employee_name'] }}</span>
                                <strong>{{ $formatMinutes($employee['total_minutes']) }}</strong>
                            </summary>
                            <div class="muted">{{ $employee['company_name'] }}</div>
                            <div>
                                Első be: {{ $employee['first_in']?->format('H:i') ?? '-' }}<br>
                                Utolsó ki: {{ $employee['last_out']?->format('H:i') ?? '-' }}<br>
                                Események: {{ $employee['event_count'] }}
                            </div>

                            @foreach ($employee['pairs'] as $pair)
                                <div class="pair">
                                    {{ $pair['in']->format('H:i') }} - {{ $pair['out']->format('H:i') }}
                                    <span>{{ $formatMinutes($pair['minutes']) }}</span>
                                </div>
                            @endforeach

                            @foreach ($employee['warnings'] as $warning)
                                <div class="badge warn">{{ $warning }}</div>
                            @endforeach
                        </details>
                    @empty
                        <div class="muted">Nincs adat</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
@endsection
