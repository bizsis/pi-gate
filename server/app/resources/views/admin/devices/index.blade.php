@extends('admin.layout', ['title' => 'Eszközök - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Eszközök</h1>
            <div class="muted">Regisztrált PDA-k és szinkron állapotok</div>
        </div>
    </div>

    <form class="panel" method="get" action="{{ route('admin.devices') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="q">Keresés</label>
                <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Név, UID, platform, app verzió">
            </div>
            <div class="form-row">
                <label for="company_id">Cég</label>
                <select id="company_id" name="company_id">
                    <option value="">Minden cég</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) ($filters['company_id'] ?? 0) === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="active">Állapot</label>
                <select id="active" name="active">
                    <option value="">Minden állapot</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Aktív</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Inaktív</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.devices') }}">Törlés</a>
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>UID</th>
                        <th>Cég</th>
                        <th>Platform</th>
                        <th>App verzió</th>
                        <th>Utolsó kapcsolat</th>
                        <th>Utolsó szinkron</th>
                        <th>Blokkolások</th>
                        <th>Naplók</th>
                        <th>Állapot</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>{{ $device->name ?? '-' }}</td>
                            <td>{{ $device->device_uid }}</td>
                            <td>{{ $device->company?->name ?? '-' }}</td>
                            <td>{{ $device->platform }}</td>
                            <td>{{ $device->app_version ?? '-' }}</td>
                            <td>{{ optional($device->last_seen_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ optional($device->last_sync_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $device->events_count }}</td>
                            <td>{{ $device->sync_logs_count }}</td>
                            <td>@include('admin.partials.status', ['active' => $device->active])</td>
                            <td><a class="action secondary" href="{{ route('admin.devices.edit', $device) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="muted">Nincs eszköz.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $devices->links() }}</div>
    </section>
@endsection
