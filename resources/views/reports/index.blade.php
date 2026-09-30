@extends('layouts.app')

@section('title', 'Отчёты и аналитика')
@section('subtitle', $from->format('d.m.Y').' — '.$to->format('d.m.Y'))

@section('actions')
    <form method="GET" class="flex">
        <select class="select" name="period" data-autosubmit style="width:180px">
            @foreach (['week' => 'Неделя', 'month' => 'Месяц', 'quarter' => 'Квартал', 'year' => 'Год', 'all' => 'Всё время'] as $key => $label)
                <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <a class="btn btn-primary" href="{{ route('reports.export', ['period' => $period]) }}">⬇ Экспорт CSV</a>
@endsection

@section('content')
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-green-line">
            <div class="label">Выручка</div>
            <div class="value">{{ number_format($revenue, 0, ',', ' ') }} ₽</div>
            <div class="hint">{{ $ordersCount }} выданных нарядов</div>
        </div>
        <div class="card kpi tone-accent-line">
            <div class="label">Средний чек</div>
            <div class="value">{{ number_format($avgCheck, 0, ',', ' ') }} ₽</div>
            <div class="hint">Фактически поступило: {{ number_format($paid, 0, ',', ' ') }} ₽</div>
        </div>
        <div class="card kpi tone-violet-line">
            <div class="label">Валовая маржа</div>
            <div class="value">{{ number_format($margin, 0, ',', ' ') }} ₽</div>
            <div class="hint">
                {{ $revenue > 0 ? round($margin / $revenue * 100).'% от выручки' : 'нет данных' }}
            </div>
        </div>
        <div class="card kpi tone-amber-line">
            <div class="label">Структура выручки</div>
            <div class="value">{{ $revenue > 0 ? round($workRevenue / $revenue * 100).'%' : '—' }}</div>
            <div class="hint">работы · запчасти {{ $revenue > 0 ? round($partsRevenue / $revenue * 100).'%' : '' }}</div>
        </div>
    </div>

    <div class="card mb-16">
        <div class="card-head">
            <h3>Выручка по дням</h3>
            <span class="small muted">Пик: {{ $chart->max('value') ? number_format($chart->max('value'), 0, ',', ' ') : 0 }} ₽</span>
        </div>
        <div class="card-body">
            <div id="report-chart"></div>
        </div>
    </div>

    <div class="grid cols-2 mb-16">
        <div class="card">
            <div class="card-head"><h3>Топ-10 работ по выручке</h3></div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Работа</th><th class="num">Кол-во</th><th class="num">Часы</th><th class="num">Выручка</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($topWorks as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td class="num mono">{{ $row->cnt }}</td>
                                    <td class="num mono">{{ number_format((float) $row->hours, 1, ',', ' ') }}</td>
                                    <td class="num mono"><b>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="empty">Нет данных за период</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Топ-10 запчастей по выручке</h3></div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Запчасть</th><th class="num">Продано</th><th class="num">Выручка</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($topParts as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td class="num mono">{{ number_format((float) $row->qty, 0, ',', ' ') }}</td>
                                    <td class="num mono"><b>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty">Нет данных за период</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="grid cols-3 mb-16">
        <div class="card">
            <div class="card-head"><h3>Марки автомобилей</h3></div>
            <div class="card-body">
                @forelse ($byBrand as $row)
                    @php $max = $byBrand->max('cnt'); @endphp
                    <div class="hbar-row">
                        <div>
                            <div class="hbar-label">
                                <b>{{ $row->brand }}</b>
                                <span>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</span>
                            </div>
                            <div class="bar"><i style="width:{{ $max ? round($row->cnt / $max * 100) : 0 }}%"></i></div>
                        </div>
                        <div class="right small muted mono">{{ $row->cnt }}</div>
                    </div>
                @empty
                    <div class="empty">Нет данных</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Нормо-часы по постам</h3></div>
            <div class="card-body">
                @forelse ($postLoad as $row)
                    @php $max = $postLoad->max('hours'); @endphp
                    <div class="hbar-row">
                        <div>
                            <div class="hbar-label">
                                <b>{{ $row->name ?? 'Без поста' }}</b>
                                <span>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</span>
                            </div>
                            <div class="bar amber"><i style="width:{{ $max ? round($row->hours / $max * 100) : 0 }}%"></i></div>
                        </div>
                        <div class="right small muted mono">{{ number_format((float) $row->hours, 1, ',', ' ') }} ч</div>
                    </div>
                @empty
                    <div class="empty">Нет данных</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Мастера: выработка</h3></div>
            <div class="card-body">
                @forelse ($byMechanic as $row)
                    @php $max = $byMechanic->max('hours'); @endphp
                    <div class="hbar-row">
                        <div>
                            <div class="hbar-label">
                                <b>{{ $row->name }}</b>
                                <span>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</span>
                            </div>
                            <div class="bar green"><i style="width:{{ $max ? round($row->hours / $max * 100) : 0 }}%"></i></div>
                        </div>
                        <div class="right small muted mono">{{ number_format((float) $row->hours, 1, ',', ' ') }} ч</div>
                    </div>
                @empty
                    <div class="empty">Нет данных</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid side-main">
        <div class="card">
            <div class="card-head"><h3>Лучшие клиенты</h3></div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Клиент</th><th class="num">Визитов</th><th class="num">Сумма</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($topCustomers as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td class="num mono">{{ $row->cnt }}</td>
                                    <td class="num mono"><b>{{ number_format((float) $row->sum, 0, ',', ' ') }} ₽</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty">Нет данных за период</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h3>Текущая загрузка постов</h3></div>
                <div class="card-body">
                    @forelse ($postLoadNow as $row)
                        @php $tone = $row['percent'] >= 100 ? 'red' : ($row['percent'] >= 70 ? 'amber' : 'green'); @endphp
                        <div class="hbar-row">
                            <div>
                                <div class="hbar-label">
                                    <b>{{ $row['name'] }}</b>
                                    <span>{{ $row['orders'] }} наряд(ов) · {{ $row['percent'] }}%</span>
                                </div>
                                <div class="bar {{ $tone }}"><i style="width:{{ min(100, $row['percent']) }}%"></i></div>
                            </div>
                            <div class="right small muted mono">{{ $row['percent'] }}%</div>
                        </div>
                    @empty
                        <div class="empty">Нет активных постов</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Заканчиваются на складе</h3></div>
                <div class="card-body tight">
                    @forelse ($lowStock as $part)
                        <div style="padding:11px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px">
                            <span>{{ $part->name }}</span>
                            <span class="badge {{ (float) $part->qty <= 0 ? 'b-red' : 'b-amber' }}">
                                {{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}
                            </span>
                        </div>
                    @empty
                        <div class="empty"><div class="big">✅</div>Всех позиций достаточно</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        DSH.lineChart('report-chart', {
            labels: @json(collect($chart)->pluck('label')->values()),
            money: true,
            height: 280,
            series: [{
                name: 'Выручка',
                color: '#2563eb',
                data: @json(collect($chart)->pluck('value')->values())
            }]
        });
    </script>
@endpush