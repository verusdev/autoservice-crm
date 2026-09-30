@extends('layouts.app')

@section('title', 'Дебиторка')
@section('subtitle', 'Неоплаченные наряды: '.number_format($debtTotal, 0, ',', ' ').' ₽')

@section('actions')
    <a class="btn" href="{{ route('payments.index') }}">Касса</a>
@endsection

@section('content')
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-red-line">
            <div class="label">Общий долг</div>
            <div class="value">{{ number_format($debtTotal, 0, ',', ' ') }} ₽</div>
            <div class="hint">{{ $byCustomer->count() }} клиент(ов)</div>
        </div>
        <div class="card kpi tone-amber-line">
            <div class="label">Нарядов в долгу</div>
            <div class="value">{{ $debtOrders->count() }}</div>
            <div class="hint">Не полностью оплачены</div>
        </div>
        <div class="card kpi tone-accent-line">
            <div class="label">Просрочено по сроку</div>
            <div class="value">{{ $overdue->count() }}</div>
            <div class="hint">На {{ number_format((float) $overdue->sum(fn ($o) => $o->balance), 0, ',', ' ') }} ₽</div>
        </div>
        <div class="card kpi tone-green-line">
            <div class="label">Факт месяца</div>
            <div class="value">{{ number_format($factMonth, 0, ',', ' ') }} ₽</div>
            <div class="hint">Выдано нарядов: {{ number_format($planMonth, 0, ',', ' ') }} ₽</div>
        </div>
    </div>

    <div class="grid main-side">
        <div class="stack">
            <div class="card">
                <div class="card-head"><h3>Долги по клиентам</h3></div>
                <div class="card-body tight">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Клиент</th>
                                    <th class="num">Нарядов</th>
                                    <th>Старейший долг</th>
                                    <th class="num">Сумма долга</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($byCustomer as $row)
                                    <tr>
                                        <td>
                                            <a class="link-plain" href="{{ route('customers.show', $row['customer']->id) }}">{{ $row['customer']?->name }}</a>
                                            <div class="small muted mono">{{ $row['customer']?->phone }}</div>
                                        </td>
                                        <td class="num">{{ $row['count'] }}</td>
                                        <td class="small muted">{{ $row['oldest']?->diffForHumans() }}</td>
                                        <td class="num"><span class="badge b-red">{{ number_format($row['debt'], 0, ',', ' ') }} ₽</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="empty"><div class="big">🎉</div>Долгов нет — все наряды оплачены</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Наряды с неоплатой</h3></div>
                <div class="card-body tight">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Наряд</th>
                                    <th>Клиент / авто</th>
                                    <th>Выдан</th>
                                    <th class="num">Итого</th>
                                    <th class="num">Оплачено</th>
                                    <th class="num">Долг</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($debtOrders as $order)
                                    <tr>
                                        <td><a class="link-plain mono" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                                        <td class="small">
                                            {{ $order->customer?->name }}
                                            <div class="muted">{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</div>
                                        </td>
                                        <td class="small muted">
                                            {{ $order->closed_at?->format('d.m.Y') ?: $order->intake_at->format('d.m.Y') }}
                                            @if ($order->promised_at && $order->promised_at->isPast() && $order->status !== 'issued')
                                                <div><span class="badge b-red">просрочен</span></div>
                                            @endif
                                        </td>
                                        <td class="num mono">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</td>
                                        <td class="num mono">{{ number_format((float) $order->paid, 0, ',', ' ') }} ₽</td>
                                        <td class="num mono"><b style="color:var(--red)">{{ number_format($order->balance, 0, ',', ' ') }} ₽</b></td>
                                        <td class="right">
                                            <a class="btn btn-sm btn-primary" href="{{ route('orders.show', $order) }}">Оплатить</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><div class="empty">Все наряды оплачены</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Поступления по месяцам</h3></div>
            <div class="card-body">
                <div id="debt-chart"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        DSH.lineChart('debt-chart', {
            labels: @json(collect($chart)->pluck('label')->values()),
            money: true,
            height: 300,
            series: [{ name: 'Поступления', color: '#16a34a', data: @json(collect($chart)->pluck('value')->values()) }]
        });
    </script>
@endpush