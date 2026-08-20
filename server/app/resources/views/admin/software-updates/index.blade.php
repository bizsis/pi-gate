@extends('admin.layout', ['title' => 'Szoftverfrissítések - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Szoftverfrissítések</h1>
            <div class="muted">PDA APK kiküldése interneten keresztül</div>
        </div>
    </div>

    <form class="panel" method="post" action="{{ route('admin.software-updates.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel-title">Új PDA verzió kiküldése</div>
        <div class="form-grid">
            <div class="form-row">
                <label for="version_code">Verzió kód</label>
                <input id="version_code" name="version_code" type="text" value="{{ old('version_code') }}" placeholder="Pl. 2">
            </div>
            <div class="form-row">
                <label for="version_name">Verzió név</label>
                <input id="version_name" name="version_name" type="text" value="{{ old('version_name') }}" placeholder="Pl. 1.2.147">
            </div>
            <div class="form-row">
                <label for="apk">APK fájl</label>
                <input id="apk" name="apk" type="file" accept=".apk">
            </div>
            <div class="form-row">
                <label for="mandatory">Telepítés</label>
                <select id="mandatory" name="mandatory">
                    <option value="1" selected>Kötelező</option>
                    <option value="0">Nem kötelező</option>
                </select>
            </div>
            <div class="form-row">
                <label for="notes">Megjegyzés</label>
                <input id="notes" name="notes" type="text" value="{{ old('notes') }}" placeholder="Mi változott?">
            </div>
        </div>
        @if ($errors->any())
            <div class="error" style="margin: 0 16px 16px;">
                {{ $errors->first() }}
            </div>
        @endif
        <div class="form-actions">
            <button class="action" type="submit">Frissítés kiküldése</button>
        </div>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Verzió</th>
                        <th>Méret</th>
                        <th>SHA-256</th>
                        <th>Kötelező</th>
                        <th>Állapot</th>
                        <th>Létrehozva</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($updates as $update)
                        <tr>
                            <td>{{ $update->version_name }} / {{ $update->version_code }}</td>
                            <td>{{ number_format($update->file_size / 1024 / 1024, 1, ',', ' ') }} MB</td>
                            <td><code>{{ $update->sha256 }}</code></td>
                            <td>{{ $update->mandatory ? 'Igen' : 'Nem' }}</td>
                            <td>@include('admin.partials.status', ['active' => $update->active])</td>
                            <td>{{ $update->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Még nincs kiküldött szoftverfrissítés.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $updates->links() }}</div>
    </section>
@endsection
