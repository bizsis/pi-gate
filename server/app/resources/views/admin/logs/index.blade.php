@extends('admin.layout', ['title' => 'Napló - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Napló</h1>
            <div class="muted">Admin felületen végzett módosítások</div>
        </div>
    </div>

    <form class="panel" method="get" action="{{ route('admin.logs') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="q">Keresés</label>
                <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Művelet, rekord, IP, felhasználó">
            </div>
            <div class="form-row">
                <label for="user_id">Felhasználó</label>
                <select id="user_id" name="user_id">
                    <option value="">Minden felhasználó</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="action">Művelet</label>
                <select id="action" name="action">
                    <option value="">Minden művelet</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="date_from">Dátumtól</label>
                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="form-row">
                <label for="date_to">Dátumig</label>
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.logs') }}">Törlés</a>
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Időpont</th>
                        <th>Felhasználó</th>
                        <th>Művelet</th>
                        <th>Rekord</th>
                        <th>IP</th>
                        <th>Változás</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->user?->email ?? '-' }}</td>
                            <td><span class="badge ok">{{ $log->action }}</span></td>
                            <td>
                                {{ $log->model_type ? class_basename($log->model_type) : '-' }}
                                @if ($log->model_id)
                                    #{{ $log->model_id }}
                                @endif
                            </td>
                            <td>{{ $log->ip_address ?? '-' }}</td>
                            <td>
                                <details>
                                    <summary>Részletek</summary>
                                    <div class="muted">Előtte</div>
                                    <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    <div class="muted">Utána</div>
                                    <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Még nincs naplóbejegyzés.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $logs->links() }}</div>
    </section>
@endsection
