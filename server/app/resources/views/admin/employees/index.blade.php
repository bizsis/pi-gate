@extends('admin.layout', ['title' => 'Dolgozók / kártyák - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Dolgozók / kártyák</h1>
            <div class="muted">Dolgozók és hozzájuk rendelt kártyaszámok</div>
        </div>
        <a class="action" href="{{ route('admin.employees.create') }}">Új dolgozó</a>
    </div>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Dolgozó</th>
                        <th>Külső azonosító</th>
                        <th>Cég</th>
                        <th>Kártyák</th>
                        <th>Állapot</th>
                        <th>Létrehozva</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->name }}</td>
                            <td>{{ $employee->external_id ?? '-' }}</td>
                            <td>{{ $employee->company?->name ?? '-' }}</td>
                            <td>
                                <div class="cards-list">
                                    @forelse ($employee->cards as $card)
                                        <span class="badge {{ $card->active ? 'ok' : 'bad' }}">{{ $card->card_number }}</span>
                                    @empty
                                        <span class="muted">Nincs kártya</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>@include('admin.partials.status', ['active' => $employee->active])</td>
                            <td>{{ optional($employee->created_at)->format('Y-m-d H:i') }}</td>
                            <td><a class="action secondary" href="{{ route('admin.employees.edit', $employee) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Nincs dolgozó.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $employees->links() }}</div>
    </section>
@endsection
