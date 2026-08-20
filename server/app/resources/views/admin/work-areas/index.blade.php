@extends('admin.layout', ['title' => 'Munkaterületek - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Munkaterületek</h1>
            <div class="muted">GPS pontok és ellenőrzési sugarak a blokkolásokhoz</div>
        </div>
        <a class="action" href="{{ route('admin.work-areas.create') }}">Új munkaterület</a>
    </div>

    <form class="panel" method="get" action="{{ route('admin.work-areas') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="q">Keresés</label>
                <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Munkaterület vagy cég">
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
                    <option value="">Minden</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Aktív</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Inaktív</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.work-areas') }}">Törlés</a>
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Cég</th>
                        <th>GPS</th>
                        <th>Sugár</th>
                        <th>Állapot</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workAreas as $workArea)
                        <tr>
                            <td><strong>{{ $workArea->name }}</strong></td>
                            <td>{{ $workArea->company?->name ?? '-' }}</td>
                            <td>
                                <a
                                    href="https://www.google.com/maps?q={{ $workArea->latitude }},{{ $workArea->longitude }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {{ $workArea->latitude }}, {{ $workArea->longitude }}
                                </a>
                            </td>
                            <td>{{ $workArea->radius_meters }} m</td>
                            <td><span class="badge {{ $workArea->active ? 'ok' : 'bad' }}">{{ $workArea->active ? 'Aktív' : 'Inaktív' }}</span></td>
                            <td><a class="action secondary" href="{{ route('admin.work-areas.edit', $workArea) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Nincs munkaterület.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $workAreas->links() }}</div>
    </section>
@endsection
