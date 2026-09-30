@extends('layouts.app')

@section('title', 'Дашборд')
@section('subtitle', 'Сводка по сервису на '.now()->format('d.m.Y'))

@section('actions')
    <a class="btn" href="{{ route('orders.index') }}">📋 Все наряды</a>
    <a class="btn btn-primary" href="{{ route('orders.create') }}">+ Новый наряд</a>
@endsection

@section('content')
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-green-line">
            <div class="label"><span class="badge-icon tone-green">💰</span> Выручка сегодня</div>
            <div class="value">{{ number_format((float) $issuedToday->revenue, 0, ',', ' ') }} ₽</div>
            <div class="hint">Выдано нарядов: {{ $issuedToday->cnt }}</div>
        </div>
        <div class="card kpi tone-accent-line">
            <div class="label"><span class="badge-icon tone-accent">💳</span> Принято оплат</div>
            <div class="value">{{ number_format((float) $cashToday, 0, ',', ' ') }} ₽</div>
            <div class="hint">По всем способам оплаты</div>
        </div>
        <div class="card kpi tone-violet-line">
            <div class="label"><span class="badge-icon tone-violet">📈</span> Выручка за месяц</div>
            <div class="value">{{ number_format((float) $revenueMonth, 0, ',', ' ') }} ₽</div>
            <div class="hint">Средний чек: {{ number_format($avgCheck, 0, ',', ' ') }} ₽</div>
        </div>
        <div class="card kpi tone-red-line">
            <div class="label"><span class="badge-icon tone-red">⚠️</span> Дебиторка</div>
            <div class="value">{{ number_format((float) $debts, 0, ',', ' ') }} ₽</div>
            <div class="hint">Нарядов в работе: {{ $openOrders }} (на {{ number_format((float) $wipSum, 0, ',', ' ') }} ₽)</div>
        </div>
    </div>

    <div class="grid main-side mb-16">
        <div class="card">
            <div class="card-head">
                <h3>Выручка по дням — последние 30 дней</h3>
                <a class="btn btn-sm" href="{{ route('reports.index') }}">Подробный отчёт</a>
            </div>
            <div class="card-body">
                <div id="revenue-chart"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Статусы нарядов за месяц</h3></div>
            <div class="card-body">
                <div id="status-chart"></div>
            </div>
        </div>
    </div>

    <div class="grid main-side mb-16">
        <div class="card">
            <div class="card-head"><h3>Выручка по месяцам</h3></div>
            <div class="card-body">
                <div id="monthly-chart"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Загрузка постов</h3></div>
            <div class="card-body">
                @forelse ($postsLoad as $post)
                    @php $tone = $post['percent'] >= 100 ? 'red' : ($post['percent'] >= 70 ? 'amber' : 'green'); @endphp
                    <div class="hbar-row">
                        <div>
                            <div class="hbar-label">
                                <b>{{ $post['name'] }}</b>
                                <span>{{ $post['hours'] }} ч · {{ $post['percent'] }}%</span>
                            </div>
                            <div class="bar {{ $tone }}"><i style="width: {{ min(100, $post['percent']) }}%"></i></div>
                        </div>
                        <div class="right small muted mono">{{ $post['hours'] }}</div>
                    </div>
                @empty
                    <div class="empty">Активных постов нет</div>
                @endforelse
                <div class="hint-box mt-16">
                    В работе: <b>{{ $inWork }}</b> нарядов · Готовы к выдаче: <b>{{ $ready }}</b>
                </div>
            </div>
        </div>
    </div>

    <div class="grid cols-3">
        <div class="card">
            <div class="card-head"><h3>Популярные работы месяца</h3></div>
            <div class="card-body">
                @forelse ($topWorks as $work)
                    <div class="hbar-row">
                        <div>
                            <div class="hbar-label">
                                <b>{{ $work->name }}</b>
                                <span>{{ $work->cnt }}× · {{ number_format((float) $work->sum, 0, ',', ' ') }} ₽</span>
                            </div>
                            <div class="bar"><i style="width: {{ $topWorks->max('cnt') ? round($work->cnt / $topWorks->max('cnt') * 100) : 0 }}%"></i></div>
                        </div>
                        <div class="right small muted mono">{{ $work->cnt }}</div>
                    </div>
                @empty
                    <div class="empty">Пока нет выполненных работ</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Скоро ТО</h3></div>
            <div class="card-body tight">
                @forelse ($serviceDue as $vehicle)
                    <div style="padding:11px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px;align-items:center">
                        <div style="min-width:0">
                            <a class="link-plain" href="{{ route('customers.show', $vehicle->customer_id) }}">{{ $vehicle->brand }} {{ $vehicle->model }}</a>
                            <div class="small muted">{{ $vehicle->customer?->name }} · {{ number_format($vehicle->mileage, 0, ',', ' ') }} км</div>
                        </div>
                        @if ($vehicle->next_service_date->isPast())
                            <span class="badge b-red">Просрочено</span>
                        @else
                            <span class="badge b-amber">через {{ $vehicle->next_service_date->diffForHumans() }}</span>
                        @endif
                    </div>
                @empty
                    <div class="empty"><div class="big">🗓️</div>ТО не запланировано</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Заканчиваются на складе</h3>
                <a class="btn btn-sm" href="{{ route('parts.index', ['low' => 1]) }}">Все</a>
            </div>
            <div class="card-body tight">
                @forelse ($lowStock as $part)
                    <div style="padding:11px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px;align-items:center">
                        <div style="min-width:0">
                            <div class="link-plain">{{ $part->name }}</div>
                            <div class="small muted">{{ $part->brand }} {{ $part->sku ? '· '.$part->sku : '' }}</div>
                        </div>
                        <span class="badge {{ $part->qty <= 0 ? 'b-red' : 'b-amber' }}">
                            {{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}
                        </span>
                    </div>
                @empty
                    <div class="empty"><div class="big">✅</div>Всех позиций достаточно</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        DSH.lineChart('revenue-chart', {
            labels: @json(collect($days)->pluck('label')->values()),
            money: true,
            series: [{ name: 'Выручка', color: '#2563eb', data: @json(collect($days)->pluck('value')->values()) }]
        });

        DSH.lineChart('monthly-chart', {
            labels: @json(collect($months)->pluck('label')->values()),
            money: true,
            height: 250,
            series: [{ name: 'Выручка', color: '#7c3aed', data: @json(collect($months)->pluck('value')->values()) }]
        });

        DSH.donutChart('status-chart', {
            money: false,
            slices: @json($statusSlices),
            centerLabel: 'нарядов'
        });
    </script>
@endpush