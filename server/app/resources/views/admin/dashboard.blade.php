@extends('admin.layout', ['title' => 'Dashboard - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <div class="muted">Áttekintés a PI Gate adatairól</div>
        </div>
    </div>

    <section class="grid">
        <div class="stat"><div class="value">{{ $companyCount }}</div><div class="label">Cégek, ebből aktív: {{ $activeCompanyCount }}</div></div>
        <div class="stat"><div class="value">{{ $employeeCount }}</div><div class="label">Dolgozók, ebből aktív: {{ $activeEmployeeCount }}</div></div>
        <div class="stat"><div class="value">{{ $cardCount }}</div><div class="label">Kártyák, ebből aktív: {{ $activeCardCount }}</div></div>
        <div class="stat"><div class="value">{{ $deviceCount }}</div><div class="label">Eszközök, ebből aktív: {{ $activeDeviceCount }}</div></div>
        <div class="stat"><div class="value">{{ $eventCount }}</div><div class="label">Blokkolási események</div></div>
        <div class="stat"><div class="value">{{ $photoCount }}</div><div class="label">Feltöltött fotók</div></div>
    </section>

    <section class="panel">
        <div class="panel-title">Legutóbbi blokkolások</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Időpont</th>
                        <th>Cég</th>
                        <th>Dolgozó</th>
                        <th>Kártya</th>
                        <th>Eszköz</th>
                        <th>Típus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestEvents as $event)
                        <tr>
                            <td>{{ optional($event->event_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->company?->name ?? '-' }}</td>
                            <td>{{ $event->employee?->name ?? '-' }}</td>
                            <td>{{ $event->card?->card_number ?? '-' }}</td>
                            <td>{{ $event->device?->name ?: $event->device?->device_uid }}</td>
                            <td><span class="badge {{ $event->event_type === 'IN' ? 'ok' : 'warn' }}">{{ $event->event_type }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Nincs megjeleníthető blokkolás.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">Legutóbbi szinkron naplók</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Létrehozva</th>
                        <th>Eszköz</th>
                        <th>Cég</th>
                        <th>Típus</th>
                        <th>Állapot</th>
                        <th>Esemény</th>
                        <th>Dolgozó</th>
                        <th>Üzenet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestSyncLogs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->device?->name ?: $log->device?->device_uid }}</td>
                            <td>{{ $log->device?->company?->name ?? '-' }}</td>
                            <td>{{ $log->sync_type ?? '-' }}</td>
                            <td><span class="badge {{ $log->status === 'success' ? 'ok' : 'warn' }}">{{ $log->status }}</span></td>
                            <td>{{ $log->sent_events }}</td>
                            <td>{{ $log->received_employees }}</td>
                            <td>{{ $log->message ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">Nincs szinkron napló.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
