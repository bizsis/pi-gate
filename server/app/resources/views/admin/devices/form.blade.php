@extends('admin.layout', ['title' => 'Eszköz szerkesztése - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Eszköz szerkesztése</h1>
            <div class="muted">{{ $device->device_uid }}</div>
        </div>
        <a class="action secondary" href="{{ route('admin.devices') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ route('admin.devices.update', $device) }}">
        @csrf
        @method('put')

        <div class="form-grid">
            <div class="form-row">
                <label for="company_id">Cég</label>
                <select id="company_id" name="company_id" required>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id', $device->company_id) === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="name">Név</label>
                <input id="name" name="name" type="text" value="{{ old('name', $device->name) }}">
            </div>
            <div class="form-row">
                <label for="platform">Platform</label>
                <input id="platform" name="platform" type="text" value="{{ old('platform', $device->platform) }}" required>
            </div>
            <div class="form-row">
                <label for="app_version">App verzió</label>
                <input id="app_version" name="app_version" type="text" value="{{ old('app_version', $device->app_version) }}">
            </div>
            <div class="form-row">
                <label for="active">Állapot</label>
                <select id="active" name="active">
                    <option value="1" @selected((string) old('active', (int) $device->active) === '1')>Aktív</option>
                    <option value="0" @selected((string) old('active', (int) $device->active) === '0')>Inaktív</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.devices') }}">Mégse</a>
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>
@endsection
