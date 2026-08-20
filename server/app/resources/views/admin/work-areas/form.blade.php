@extends('admin.layout', ['title' => $title . ' - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="muted">Munkaterület elnevezése és GPS ellenőrzési pontja</div>
        </div>
        <a class="action secondary" href="{{ route('admin.work-areas') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ $workArea->exists ? route('admin.work-areas.update', $workArea) : route('admin.work-areas.store') }}">
        @csrf
        @if ($workArea->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-row">
                <label for="company_id">Cég</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Válassz céget</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id', $workArea->company_id) === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="name">Munkaterület neve</label>
                <input id="name" name="name" type="text" value="{{ old('name', $workArea->name) }}" required>
            </div>
            <div class="form-row">
                <label for="latitude">Szélesség</label>
                <input id="latitude" name="latitude" type="text" value="{{ old('latitude', $workArea->latitude) }}" required>
            </div>
            <div class="form-row">
                <label for="longitude">Hosszúság</label>
                <input id="longitude" name="longitude" type="text" value="{{ old('longitude', $workArea->longitude) }}" required>
            </div>
            <div class="form-row">
                <label for="radius_meters">Megengedett eltérés</label>
                <input id="radius_meters" name="radius_meters" type="text" value="{{ old('radius_meters', $workArea->radius_meters ?? 50) }}" required>
            </div>
            <div class="form-row">
                <label for="active">Állapot</label>
                <select id="active" name="active">
                    <option value="1" @selected((string) old('active', $workArea->active ? '1' : '0') === '1')>Aktív</option>
                    <option value="0" @selected((string) old('active', $workArea->active ? '1' : '0') === '0')>Inaktív</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>

    @if ($workArea->exists)
        <section class="panel danger">
            <div class="panel-title">Törlés</div>
            <form method="post" action="{{ route('admin.work-areas.destroy', $workArea) }}">
                @csrf
                @method('delete')
                <div class="form-actions">
                    <button class="action danger" type="submit">Munkaterület inaktiválása</button>
                </div>
            </form>
        </section>
    @endif
@endsection
