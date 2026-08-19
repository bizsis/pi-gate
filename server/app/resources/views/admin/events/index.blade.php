@extends('admin.layout', ['title' => 'Blokkolások - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Blokkolások</h1>
            <div class="muted">Feltöltött belépési és kilépési események</div>
        </div>
    </div>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Feltöltve</th>
                        <th>Időpont</th>
                        <th>Cég</th>
                        <th>Dolgozó</th>
                        <th>Kártya</th>
                        <th>Eszköz</th>
                        <th>Típus</th>
                        <th>GPS</th>
                        <th>Fotók</th>
                        <th>Mobil UUID</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ optional($event->created_at)->format('Y-m-d H:i:s') }}</td>
                            <td>{{ optional($event->event_at)->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $event->company?->name ?? '-' }}</td>
                            <td>{{ $event->employee?->name ?? '-' }}</td>
                            <td>{{ $event->card?->card_number ?? '-' }}</td>
                            <td>{{ $event->device?->name ?: $event->device?->device_uid }}</td>
                            <td><span class="badge {{ $event->event_type === 'IN' ? 'ok' : 'warn' }}">{{ $event->event_type }}</span></td>
                            <td>
                                @if ($event->latitude && $event->longitude)
                                    <a
                                        href="https://www.google.com/maps?q={{ $event->latitude }},{{ $event->longitude }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ $event->latitude }}, {{ $event->longitude }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($event->photos->isNotEmpty())
                                    <a href="{{ route('admin.photos.show', $event->photos->first()) }}" target="_blank">
                                        <img class="thumb" src="{{ route('admin.photos.show', $event->photos->first()) }}" alt="Blokkolási fotó">
                                    </a>
                                @else
                                    <span class="muted">Nincs fotó</span>
                                @endif
                            </td>
                            <td>{{ $event->client_event_uuid }}</td>
                            <td><a class="action secondary" href="{{ route('admin.events.edit', $event) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="muted">Nincs blokkolás.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $events->links() }}</div>
    </section>
@endsection
