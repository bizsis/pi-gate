@extends('admin.layout', ['title' => $title . ' - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="muted">Dolgozó adatai és kártyái</div>
        </div>
        <a class="action secondary" href="{{ route('admin.employees') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ $employee->exists ? route('admin.employees.update', $employee) : route('admin.employees.store') }}">
        @csrf
        @if ($employee->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-row">
                <label for="company_id">Cég</label>
                <select id="company_id" name="company_id" required>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id', $employee->company_id) === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="name">Név</label>
                <input id="name" name="name" type="text" value="{{ old('name', $employee->name) }}" required>
            </div>
            <div class="form-row">
                <label for="external_id">Külső azonosító</label>
                <input id="external_id" name="external_id" type="text" value="{{ old('external_id', $employee->external_id) }}">
            </div>
            <div class="form-row">
                <label for="active">Állapot</label>
                <select id="active" name="active">
                    <option value="1" @selected((string) old('active', (int) $employee->active) === '1')>Aktív</option>
                    <option value="0" @selected((string) old('active', (int) $employee->active) === '0')>Inaktív</option>
                </select>
            </div>
        </div>

        @if ($employee->exists)
            <div class="panel-title">Meglévő kártyák</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kártyaszám</th>
                            <th>Állapot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->cards as $card)
                            <tr>
                                <td>
                                    <input name="cards[{{ $card->id }}][card_number]" type="text" value="{{ old('cards.' . $card->id . '.card_number', $card->card_number) }}" required>
                                </td>
                                <td>
                                    <select name="cards[{{ $card->id }}][active]">
                                        <option value="1" @selected((string) old('cards.' . $card->id . '.active', (int) $card->active) === '1')>Aktív</option>
                                        <option value="0" @selected((string) old('cards.' . $card->id . '.active', (int) $card->active) === '0')>Inaktív</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted">Nincs meglévő kártya.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="form-grid">
            <div class="form-row">
                <label for="card_number">{{ $employee->exists ? 'Új kártyaszám hozzáadása' : 'Kártyaszám' }}</label>
                <input id="card_number" name="card_number" type="text" value="{{ old('card_number') }}">
            </div>
        </div>

        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.employees') }}">Mégse</a>
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>

    @if ($employee->exists && auth()->user()?->isAdmin())
        <section class="panel danger">
            <div class="panel-title">Törlés</div>
            <div class="form-grid">
                <div class="muted">
                    A dolgozó törlése inaktiválja a dolgozót és a kártyáit. A korábbi blokkolások megmaradnak.
                </div>
            </div>
            <form method="post" action="{{ route('admin.employees.destroy', $employee) }}" onsubmit="return confirm('Biztosan törlöd/inaktiválod ezt a dolgozót?');">
                @csrf
                @method('delete')
                <div class="form-actions">
                    <button class="action danger" type="submit">Törlés</button>
                </div>
            </form>
        </section>
    @endif
@endsection
