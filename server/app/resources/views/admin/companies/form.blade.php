@extends('admin.layout', ['title' => $title . ' - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="muted">Cég alapadatok</div>
        </div>
        <a class="action secondary" href="{{ route('admin.companies') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store') }}">
        @csrf
        @if ($company->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-row">
                <label for="name">Név</label>
                <input id="name" name="name" type="text" value="{{ old('name', $company->name) }}" required>
            </div>
            <div class="form-row">
                <label for="short_name">Rövid név</label>
                <input id="short_name" name="short_name" type="text" value="{{ old('short_name', $company->short_name) }}">
            </div>
            <div class="form-row">
                <label for="tax_number">Adószám</label>
                <input id="tax_number" name="tax_number" type="text" value="{{ old('tax_number', $company->tax_number) }}">
            </div>
            <div class="form-row">
                <label for="registration_number">Cégjegyzékszám</label>
                <input id="registration_number" name="registration_number" type="text" value="{{ old('registration_number', $company->registration_number) }}">
            </div>
            <div class="form-row">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $company->email) }}">
            </div>
            <div class="form-row">
                <label for="phone">Telefon</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $company->phone) }}">
            </div>
            <div class="form-row">
                <label for="address">Cím</label>
                <input id="address" name="address" type="text" value="{{ old('address', $company->address) }}">
            </div>
            <div class="form-row">
                <label for="active">Állapot</label>
                <select id="active" name="active">
                    <option value="1" @selected((string) old('active', (int) $company->active) === '1')>Aktív</option>
                    <option value="0" @selected((string) old('active', (int) $company->active) === '0')>Inaktív</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.companies') }}">Mégse</a>
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>

    @if ($company->exists && auth()->user()?->isAdmin())
        <section class="panel danger">
            <div class="panel-title">Törlés</div>
            <div class="form-grid">
                <div class="muted">
                    A cég törlése inaktiválja a céget, a dolgozóit, kártyáit és eszközeit. A korábbi blokkolások megmaradnak.
                </div>
            </div>
            <form method="post" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('Biztosan törlöd/inaktiválod ezt a céget?');">
                @csrf
                @method('delete')
                <div class="form-actions">
                    <button class="action danger" type="submit">Törlés</button>
                </div>
            </form>
        </section>
    @endif
@endsection
