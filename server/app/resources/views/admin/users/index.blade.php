@extends('admin.layout', ['title' => 'Felhasználók - PI Gate Admin'])

@section('body')
    <div class="page-head">
        <div>
            <h1>Felhasználók</h1>
            <div class="muted">Admin belépések és jogosultságok</div>
        </div>
        <a class="action" href="{{ route('admin.users.create') }}">Új felhasználó</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Email</th>
                        <th>Jogosultság</th>
                        <th>Létrehozva</th>
                        <th>Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'ok' : 'warn' }}">
                                    {{ \App\Models\User::roles()[$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.users.edit', $user) }}">Szerkesztés</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Nincs felhasználó.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $users->links() }}</div>
    </section>
@endsection

