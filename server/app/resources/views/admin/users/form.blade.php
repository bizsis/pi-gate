@extends('admin.layout', ['title' => $title . ' - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="muted">Belépési adatok és szerepkör</div>
        </div>
        <a class="action secondary" href="{{ route('admin.users') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ $adminUser->exists ? route('admin.users.update', $adminUser) : route('admin.users.store') }}">
        @csrf
        @if ($adminUser->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-row">
                <label for="name">Név</label>
                <input id="name" name="name" type="text" value="{{ old('name', $adminUser->name) }}" required>
            </div>
            <div class="form-row">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $adminUser->email) }}" required>
            </div>
            <div class="form-row">
                <label for="role">Jogosultság</label>
                <select id="role" name="role">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $adminUser->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="password">{{ $adminUser->exists ? 'Új jelszó' : 'Jelszó' }}</label>
                <input id="password" name="password" type="password" {{ $adminUser->exists ? '' : 'required' }}>
                @if ($adminUser->exists)
                    <div class="muted">Csak akkor töltsd ki, ha jelszót is módosítasz.</div>
                @endif
            </div>
            <div class="form-row">
                <label for="password_confirmation">Jelszó megerősítése</label>
                <input id="password_confirmation" name="password_confirmation" type="password" {{ $adminUser->exists ? '' : 'required' }}>
            </div>
        </div>

        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.users') }}">Mégse</a>
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>

    @if ($adminUser->exists && (int) auth()->id() !== (int) $adminUser->id)
        <section class="panel danger">
            <div class="panel-title">Törlés</div>
            <div class="form-grid">
                <div class="muted">
                    A felhasználó törlése megszünteti az admin felülethez való hozzáférését. Az admin napló megmarad.
                </div>
            </div>
            <form method="post" action="{{ route('admin.users.destroy', $adminUser) }}" onsubmit="return confirm('Biztosan törlöd ezt a felhasználót?');">
                @csrf
                @method('delete')
                <div class="form-actions">
                    <button class="action danger" type="submit">Törlés</button>
                </div>
            </form>
        </section>
    @endif
@endsection

