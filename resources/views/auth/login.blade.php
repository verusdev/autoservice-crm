<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="brand-line">
            <div class="logo" style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:grid;place-items:center;font-size:20px">🔧</div>
            <div>
                <div style="font-weight:700;font-size:15px">{{ config('app.name') }}</div>
                <div class="small muted">CRM автосервиса</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="stack" style="gap:14px">
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input class="input" type="email" id="email" name="email" value="{{ old('email', 'admin@autoservice.ru') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input class="input" type="password" id="password" name="password" value="{{ old('password', 'password') }}" required>
            </div>
            <label class="check">
                <input type="checkbox" name="remember" value="1"> Запомнить меня
            </label>
            <button class="btn btn-primary btn-block" type="submit">Войти</button>
        </form>

        <div class="hint-box mt-16">
            Демо-доступ: <b>admin@autoservice.ru</b> / <b>password</b>
        </div>
    </div>
</div>
</body>
</html>