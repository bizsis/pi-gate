@extends('admin.layout', ['title' => 'Blokkolás szerkesztése - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Blokkolás szerkesztése</h1>
            <div class="muted">{{ $event->company?->name }} · {{ $event->client_event_uuid }}</div>
        </div>
        <a class="action secondary" href="{{ route('admin.events') }}">Vissza</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" method="post" action="{{ route('admin.events.update', $event) }}">
        @csrf
        @method('put')

        <div class="form-grid">
            <div class="form-row">
                <label>Cég</label>
                <input type="text" value="{{ $event->company?->name ?? '-' }}" disabled>
            </div>
            <div class="form-row">
                <label for="employee_id">Dolgozó</label>
                <select id="employee_id" name="employee_id" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) old('employee_id', $event->employee_id) === $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="card_id">Kártya</label>
                <select id="card_id" name="card_id">
                    <option value="">Nincs kártya</option>
                    @foreach ($cards as $card)
                        <option value="{{ $card->id }}" @selected((int) old('card_id', $event->card_id) === $card->id)>{{ $card->card_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="device_id">Eszköz</label>
                <select id="device_id" name="device_id" required>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected((int) old('device_id', $event->device_id) === $device->id)>{{ $device->name ?: $device->device_uid }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label for="event_type">Típus</label>
                <select id="event_type" name="event_type">
                    <option value="IN" @selected(old('event_type', $event->event_type) === 'IN')>IN - belépés</option>
                    <option value="OUT" @selected(old('event_type', $event->event_type) === 'OUT')>OUT - kilépés</option>
                </select>
            </div>
            <div class="form-row">
                <label for="event_at">Időpont</label>
                <input id="event_at" name="event_at" type="datetime-local" step="1" value="{{ old('event_at', optional($event->event_at)->format('Y-m-d\\TH:i:s')) }}" required>
            </div>
            <div class="form-row">
                <label for="latitude">GPS szélesség</label>
                <input id="latitude" name="latitude" type="text" value="{{ old('latitude', $event->latitude) }}">
            </div>
            <div class="form-row">
                <label for="longitude">GPS hosszúság</label>
                <input id="longitude" name="longitude" type="text" value="{{ old('longitude', $event->longitude) }}">
            </div>
        </div>

        @if ($event->photos->isNotEmpty())
            <div class="panel-title">Kapcsolódó fotók</div>
            <div class="form-grid">
                @foreach ($event->photos as $photo)
                    <a href="{{ route('admin.photos.show', $photo) }}" target="_blank" rel="noopener noreferrer">
                        <img class="thumb" src="{{ route('admin.photos.show', $photo) }}" alt="Blokkolási fotó">
                    </a>
                @endforeach
            </div>
        @endif

        <div class="form-actions">
            <a class="action secondary" href="{{ route('admin.events') }}">Mégse</a>
            <button class="action" type="submit">Mentés</button>
        </div>
    </form>

    @if (auth()->user()?->isAdmin())
        <section class="panel danger">
            <div class="panel-title">Törlés</div>
            <div class="form-grid">
                <div class="muted">
                    A blokkolás törlése véglegesen törli a blokkolást és a hozzá tartozó szerveren tárolt fotókat.
                </div>
            </div>
            <form method="post" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Biztosan véglegesen törlöd ezt a blokkolást?');">
                @csrf
                @method('delete')
                <div class="form-actions">
                    <button class="action danger" type="submit">Törlés</button>
                </div>
            </form>
        </section>
    @endif
@endsection
