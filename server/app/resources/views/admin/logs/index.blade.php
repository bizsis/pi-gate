@extends('admin.layout', ['title' => 'Napló - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Napló</h1>
            <div class="muted">Admin felületen végzett módosítások</div>
        </div>
    </div>

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
