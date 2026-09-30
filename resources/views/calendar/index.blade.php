@extends('layouts.app')

@section('title', 'Календарь загрузки')
@section('subtitle', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
@endpush

@section('actions')
    <div class="cal-switch">
        @foreach (['day' => 'День', 'week' => 'Неделя', 'month' => 'Месяц'] as $key => $label)
            <a href="{{ route('calendar.index', ['view' => $key, 'date' => $anchor->format('Y-m-d')]) }}"
               class="{{ $view === $key ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
@endsection

@section('content')
    {{-- Панель периода --}}
    <div class="card mb-16">
        <div class="card-body cal-toolbar">
            <div class="flex">
                <a class="btn btn-sm" href="{{ route('calendar.index', ['view' => $view, 'date' => $prev]) }}">←</a>
                <a class="btn btn-sm" href="{{ route('calendar.index', ['view' => $view, 'date' => today()->format('Y-m-d')]) }}">Сегодня</a>
                <a class="btn btn-sm" href="{{ route('calendar.index', ['view' => $view, 'date' => $next]) }}">→</a>
                <b style="font-size:15px;margin-left:6px">{{ $title }}</b>
            </div>
            <div class="cal-legend">
                <span><i style="background:#2563eb"></i>Новый</span>
                <span><i style="background:#d97706"></i>В работе</span>
                <span><i style="background:#7c3aed"></i>Ждёт запчасть</span>
                <span><i style="background:#16a34a"></i>Готов</span>
                <span><i style="background:#94a3b8"></i>Выдан</span>
                <span><i style="background:#dc2626"></i>Просрочен срок</span>
            </div>
        </div>
    </div>

    {{-- Итоги периода --}}
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-line">
            <div class="label">Нарядов в сервисе</div>
            <div class="value">{{ $totals['orders'] }}</div>
            <div class="hint">Присутствуют в выбранном периоде</div>
        </div>
        <div class="card kpi tone-amber-line">
            <div class="label">Осталось нормо-часов</div>
            <div class="value">{{ number_format($totals['hours'], 1, ',', ' ') }} ч</div>
            <div class="hint">Пиковая загрузка дня: {{ $totals['loadPercent'] }}%</div>
        </div>
        <div class="card kpi tone-green-line">
            <div class="label">Выдано за период</div>
            <div class="value">{{ $totals['issued'] }}</div>
            <div class="hint">На {{ number_format($totals['revenue'], 0, ',', ' ') }} ₽</div>
        </div>
        <div class="card kpi tone-red-line">
            <div class="label">Просрочено сроков</div>
            <div class="value">{{ $overdue->count() }}</div>
            <div class="hint">
                @if ($overdue->isNotEmpty())
                    Ближайший: {{ $overdue->first()->promised_at->diffForHumans() }}
                @else
                    Всё в срок
                @endif
            </div>
        </div>
    </div>

    @if ($view === 'month')
        {{-- Месячная сетка --}}
        <div class="card mb-16">
            <div class="card-head">
                <h3>Плотность загрузки по дням</h3>
                <span class="small muted">В каждой ячейке: нарядов в сервисе · выдано · принято</span>
            </div>
            <div class="card-body tight">
                <div class="cal-month">
                    @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)
                        <div class="cal-month-head">{{ $weekday }}</div>
                    @endforeach

                    @foreach ($days as $day)
                        @php
                            $key = $day->format('Y-m-d');
                            $info = $daily[$key] ?? null;
                            $inMonth = $day->month === $anchor->month;
                            $isWeekend = in_array((int) $day->dayOfWeekIso, [6, 7], true);
                            $loadPercent = $info['loadPercent'] ?? 0;
                            $tone = $loadPercent >= 100 ? 'var(--red)' : ($loadPercent >= 60 ? 'var(--amber)' : 'var(--green)');
                        @endphp
                        <div class="cal-cell {{ $inMonth ? '' : 'out' }} {{ $key === $today ? 'today' : '' }} {{ $isWeekend ? 'cal-weekend' : '' }}">
                            <div class="num">
                                <span>{{ $day->day }}</span>
                                <span class="wd">{{ $day->translatedFormat('D') }}</span>
                            </div>

                            @if ($info && $info['ordersCount'] > 0)
                                <div class="mini">
                                    <span><b>{{ $info['ordersCount'] }}</b> в сервисе</span>
                                    @if ($info['issuedCount'])
                                        <span>· <b>{{ $info['issuedCount'] }}</b> выдано</span>
                                    @endif
                                </div>
                                <div class="cal-dots">
                                    @foreach ($info['orders']->take(14) as $order)
                                        <i style="background:{{ $order->promised_at && $order->promised_at->lt(now()) && $order->isOpen() ? '#dc2626' : match ($order->status) {
                                            'ready' => '#16a34a',
                                            'waiting' => '#7c3aed',
                                            'in_progress' => '#d97706',
                                            'issued' => '#94a3b8',
                                            default => '#2563eb',
                                        } }}" title="{{ $order->number }} — {{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}"></i>
                                    @endforeach
                                </div>
                                <div class="small muted mono">{{ rtrim(rtrim(number_format((float) $info['load'], 1, ',', ' '), '0'), ',') }} ч · {{ $loadPercent }}%</div>
                            @endif

                            <div class="bar"><i style="width:{{ min(100, $loadPercent) }}%;background:{{ $tone }}"></i></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif ($view === 'day')
        {{-- День: колонка = пост --}}
        @php $blocks = collect($daily[$days->first()->format('Y-m-d')]['posts'] ?? [])->keyBy('post.id'); @endphp
        <div class="card mb-16">
            <div class="card-head">
                <h3>Машины на постах — {{ $days->first()->translatedFormat('d F Y') }}</h3>
                <span class="small muted">Норма-часы = незакрытые работы по нарядам в сервисе</span>
            </div>
            <div class="card-body">
                <div class="cal-board">
                    @foreach ($posts as $post)
                        @php
                            $block = $blocks[$post->id] ?? null;
                            $orders = $block['orders'] ?? collect();
                            $percent = $block['percent'] ?? 0;
                            $hours = $block['hours'] ?? 0;
                            $tone = $percent >= 100 ? 'red' : ($percent >= 60 ? 'amber' : 'green');
                        @endphp
                        <div class="cal-col">
                            <div class="cal-col-head">
                                <div class="flex-between">
                                    <span class="name">{{ $post->name }}</span>
                                    <span class="small muted mono">{{ $percent }}%</span>
                                </div>
                                <div class="bar {{ $tone }}" style="margin-top:7px">
                                    <i style="width:{{ min(100, $percent) }}%"></i>
                                </div>
                                <div class="small muted" style="margin-top:5px">
                                    {{ rtrim(rtrim(number_format((float) $hours, 1, ',', ' '), '0'), ',') }} ч · {{ $orders->count() }} наряд(ов) · {{ $post->capacity_hours }} ч/смена
                                </div>
                            </div>
                            <div class="cal-col-body">
                                @forelse ($orders as $order)
                                    @include('calendar.card', ['order' => $order, 'compact' => false])
                                @empty
                                    <div class="cal-col-empty">Свободен</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        {{-- Неделя: строки = посты, колонки = дни --}}
        @php
            $blocks = $days->mapWithKeys(fn ($day) => [
                $day->format('Y-m-d') => collect($daily[$day->format('Y-m-d')]['posts'] ?? [])->keyBy('post.id'),
            ]);
        @endphp
        <div class="card mb-16">
            <div class="card-head">
                <h3>Загрузка постов по дням</h3>
                <span class="small muted">Норма-часы = незакрытые работы по нарядам в сервисе</span>
            </div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table class="cal-week">
                        <thead>
                            <tr>
                                <th style="width:180px">Пост</th>
                                @foreach ($days as $day)
                                    @php
                                        $info = $daily[$day->format('Y-m-d')] ?? null;
                                        $pct = $info['loadPercent'] ?? 0;
                                    @endphp
                                    <th class="cal-day-head {{ $day->format('Y-m-d') === $today ? 'on' : '' }}">
                                        <div>{{ $day->translatedFormat('D') }} · {{ $day->format('d.m') }}</div>
                                        <div class="small muted">
                                            {{ $info['ordersCount'] ?? 0 }} наряд(ов)
                                            @if ($info && $info['issuedCount']) · выдано {{ $info['issuedCount'] }} @endif
                                        </div>
                                        <div class="bar" style="margin-top:5px">
                                            <i style="width:{{ min(100, $pct) }}%;background:{{ $pct >= 100 ? 'var(--red)' : ($pct >= 60 ? 'var(--amber)' : 'var(--green)') }}"></i>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td style="vertical-align:top">
                                        <b>{{ $post->name }}</b>
                                        <div class="small muted">{{ $post->capacity_hours }} ч/смена</div>
                                    </td>
                                    @foreach ($days as $day)
                                        @php
                                            $key = $day->format('Y-m-d');
                                            $block = $blocks[$key][$post->id] ?? null;
                                            $orders = $block['orders'] ?? collect();
                                            $percent = $block['percent'] ?? 0;
                                        @endphp
                                        <td class="cal-cell-{{ $key === $today ? 'today' : '' }}">
                                            @if ($orders->isNotEmpty())
                                                <div class="small muted mono" style="margin-bottom:5px">
                                                    {{ rtrim(rtrim(number_format((float) $block['hours'], 1, ',', ' '), '0'), ',') }} ч · {{ $percent }}%
                                                </div>
                                                @foreach ($orders->take(4) as $order)
                                                    @include('calendar.card', ['order' => $order, 'compact' => true])
                                                @endforeach
                                                @if ($orders->count() > 4)
                                                    <a class="small muted" href="{{ route('orders.index', ['post' => $post->id, 'from' => $key, 'to' => $key]) }}">
                                                        ещё {{ $orders->count() - 4 }} →
                                                    </a>
                                                @endif
                                            @else
                                                <span class="small muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="grid cols-3">
        {{-- Просроченные сроки --}}
        <div class="card">
            <div class="card-head">
                <h3>Просроченные сроки</h3>
                @if ($overdue->count())<span class="badge b-red">{{ $overdue->count() }}</span>@endif
            </div>
            <div class="card-body tight">
                @forelse ($overdue as $order)
                    <a class="cal-card st-{{ $order->status }}" style="border-radius:0;border-left-width:4px;border-top:0;border-right:0;border-bottom:0"
                       href="{{ route('orders.show', $order) }}">
                        <div class="t">
                            <span>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</span>
                            <span class="late">{{ $order->promised_at->format('d.m') }}</span>
                        </div>
                        <div class="m">{{ $order->customer?->name }} · {{ $order->number }} · {{ $order->post?->name ?: 'без поста' }}</div>
                    </a>
                @empty
                    <div class="empty"><div class="big">✅</div>Все сроки в порядке</div>
                @endforelse
            </div>
        </div>

        {{-- Ближайшие выдачи --}}
        <div class="card">
            <div class="card-head"><h3>Ближайшие выдачи</h3></div>
            <div class="card-body tight">
                @forelse ($upcoming as $order)
                    <a class="cal-card st-{{ $order->status }}" style="border-radius:0;border-left-width:4px;border-top:0;border-right:0;border-bottom:0"
                       href="{{ route('orders.show', $order) }}">
                        <div class="t">
                            <span>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</span>
                            <span class="badge b-gray">{{ $order->promised_at->diffForHumans() }}</span>
                        </div>
                        <div class="m">{{ $order->customer?->name }} · {{ \App\Models\Order::statusLabel($order->status) }}</div>
                    </a>
                @empty
                    <div class="empty"><div class="big">📅</div>Плановых выдач нет</div>
                @endforelse
            </div>
        </div>

        {{-- Плановое ТО --}}
        <div class="card">
            <div class="card-head">
                <h3>Плановое ТО</h3>
                <span class="small muted">{{ $serviceDue->count() }}</span>
            </div>
            <div class="card-body tight">
                @forelse ($serviceDue as $vehicle)
                    <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px;align-items:center">
                        <div style="min-width:0">
                            <div class="link-plain">{{ $vehicle->brand }} {{ $vehicle->model }}</div>
                            <div class="small muted">{{ $vehicle->customer?->name }} · {{ number_format($vehicle->mileage, 0, ',', ' ') }} км</div>
                        </div>
                        <span class="badge {{ $vehicle->next_service_date->isPast() ? 'b-red' : 'b-gray' }}">
                            {{ $vehicle->next_service_date->format('d.m') }}
                        </span>
                    </div>
                @empty
                    <div class="empty"><div class="big">🛠️</div>ТО не запланировано</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection