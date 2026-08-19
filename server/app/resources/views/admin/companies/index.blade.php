@extends('admin.layout', ['title' => 'Cégek - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Cégek</h1>
            <div class="muted">Regisztrált cégek és kapcsolódó darabszámok</div>
        </div>
        <a class="action" href="{{ route('admin.companies.create') }}">Új cég</a>
    </div>

    <form class="panel" method="get" action="{{ route('admin.companies') }}">
        <div class="form-grid">
            <div class="form-row">
                <label for="q">Keresés</label>
                <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Név, rövid név, adószám, email, telefon">
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
            <a class="action secondary" href="{{ route('admin.companies') }}">Törlés</a>
            <button class="action" type="submit">Szűrés</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Rövid név</th>
                        <th>Adószám</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Dolgozók</th>
                        <th>Kártyák</th>
                        <th>Eszközök</th>
                        <th>Blokkolások</th>
                        <th>Állapot</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->short_name ?? '-' }}</td>
                            <td>{{ $company->tax_number ?? '-' }}</td>
                            <td>{{ $company->email ?? '-' }}</td>
                            <td>{{ $company->phone ?? '-' }}</td>
                            <td>{{ $company->employees_count }}</td>
                            <td>{{ $company->cards_count }}</td>
                            <td>{{ $company->devices_count }}</td>
                            <td>{{ $company->events_count }}</td>
                            <td>@include('admin.partials.status', ['active' => $company->active])</td>
                            <td><a class="action secondary" href="{{ route('admin.companies.edit', $company) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="muted">Nincs cég.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $companies->links() }}</div>
    </section>
@endsection
