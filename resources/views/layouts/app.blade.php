<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM автосервиса') — {{ config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">🔧</div>
            <div>
                <div class="name">{{ config('app.name') }}</div>
                <div class="sub">CRM автосервиса</div>
            </div>
        </div>

        <nav class="nav">
            <div class="nav-group">Обзор</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="ico">📊</span> Дашборд
            </a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                <span class="ico">🗓️</span> Календарь
            </a>

            <div class="nav-group">Работа с клиентом</div>
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <span class="ico">📝</span> Заказ-наряды
            </a>
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <span class="ico">👤</span> Клиенты
            </a>
            <a href="{{ route('vehicles.index') }}" class="{{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
                <span class="ico">🚗</span> Автомобили
            </a>

            <div class="nav-group">Финансы</div>
            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <span class="ico">💳</span> Касса
            </a>
            <a href="{{ route('finance.index') }}" class="{{ request()->routeIs('finance.*') ? 'active' : '' }}">
                <span class="ico">⚠️</span> Дебиторка
            </a>

            <div class="nav-group">Склад</div>
            <a href="{{ route('stock.index') }}" class="{{ request()->routeIs('stock.*') ? 'active' : '' }}">
                <span class="ico">📦</span> Складские операции
            </a>
            <a href="{{ route('parts.index') }}" class="{{ request()->routeIs('parts.*') ? 'active' : '' }}">
                <span class="ico">🔩</span> Каталог запчастей
            </a>

            <div class="nav-group">Сервис</div>
            <a href="{{ route('posts.index') }}" class="{{ request()->routeIs('posts.*') ? 'active' : '' }}">
                <span class="ico">🏗️</span> Посты
            </a>
            <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <span class="ico">👷</span> Сотрудники
            </a>

            <div class="nav-group">Аналитика</div>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <span class="ico">📈</span> Отчёты
            </a>
        </nav>

        <div class="side-foot">
            <div class="me">
                <div class="avatar">{{ auth()->user()?->initials() }}</div>
                <div style="min-width:0">
                    <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()?->name }}</div>
                    <div>{{ \App\Models\User::roleLabel(auth()->user()?->role) }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
                @csrf
                <button class="btn btn-sm btn-block" style="background:#1e293b;border-color:#334155;color:#cbd5e1">Выйти</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px">
                <button class="btn btn-sm burger" type="button">☰</button>
                <div>
                    <h1>@yield('title', 'Дашборд')</h1>
                    <div class="sub">@yield('subtitle', now()->translatedFormat('l, d F Y г.'))</div>
                </div>
            </div>
            <div class="top-actions">
                <form method="GET" action="{{ route('calendar.index') }}" class="flex">
                    <select class="select" name="view" onchange="this.form.submit()" style="width:120px">
                        @foreach (['day' => 'День', 'week' => 'Неделя', 'month' => 'Месяц'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('view', 'week') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">🗓️ Календарь</button>
                </form>
                @yield('actions')
            </div>
        </header>

        <main class="content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('js/charts.js') }}"></script>
@stack('scripts')
</body>
</html>