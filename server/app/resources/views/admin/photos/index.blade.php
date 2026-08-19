@extends('admin.layout', ['title' => 'Fotók - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Fotók</h1>
            <div class="muted">Blokkolásokhoz feltöltött képek</div>
        </div>
    </div>

    <form class="panel" method="get" action="{{ route('admin.photos') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="q">Keresés</label>
                <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Dolgozó, eszköz, fájlnév, SHA-256">
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
                <label for="date_from">Feltöltve dátumtól</label>
                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="form-row">
                <label for="date_to">Feltöltve dátumig</label>
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.photos') }}">Törlés</a>
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kép</th>
                        <th>Feltöltve</th>
                        <th>Blokkolás ideje</th>
                        <th>Cég</th>
                        <th>Dolgozó</th>
                        <th>Eszköz</th>
                        <th>Fájl</th>
                        <th>Méret</th>
                        <th>SHA-256</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($photos as $photo)
                        <tr>
                            <td>
                                <a href="{{ route('admin.photos.show', $photo) }}" target="_blank">
                                    <img class="thumb" src="{{ route('admin.photos.show', $photo) }}" alt="Blokkolási fotó">
                                </a>
                            </td>
                            <td>{{ optional($photo->uploaded_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ optional($photo->event?->event_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ $photo->event?->company?->name ?? '-' }}</td>
                            <td>{{ $photo->event?->employee?->name ?? '-' }}</td>
                            <td>{{ $photo->event?->device?->name ?: $photo->event?->device?->device_uid }}</td>
                            <td>{{ $photo->original_name ?? $photo->path }}</td>
                            <td>{{ $photo->file_size ? number_format($photo->file_size / 1024, 1, ',', ' ') . ' KB' : '-' }}</td>
                            <td>{{ $photo->sha256 ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">Nincs feltöltött fotó.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $photos->links() }}</div>
    </section>
@endsection
