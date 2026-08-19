@extends('admin.layout', ['title' => 'Belépés - PI Gate Admin', 'loginPage' => true])

@section('body')
    <div class="login-page">
        <div class="login-box">
            <h1>PI Gate Admin</h1>
            <p class="muted">Belépés az admin felületre</p>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('admin.login.submit') }}">
                @csrf

                <label for="email">Email cím</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

                <label for="password">Jelszó</label>
                <input id="password" name="password" type="password" required>

                <label>
                    <input name="remember" type="checkbox" value="1">
                    Emlékezzen rám
                </label>

                <button class="button" type="submit">Belépés</button>
            </form>
        </div>
    </div>
@endsection
