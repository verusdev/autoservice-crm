@extends('layouts.app')

@section('title', 'Касса')
@section('subtitle', $from->format('d.m.Y').' — '.$to->format('d.m.Y'))

@section('actions')
    <form method="GET" class="flex">
        <input class="input" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
        <input class="input" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
        <select class="select" name="method" data-autosubmit style="width:160px">
            <option value="">Все способы</option>
            @foreach ($methods as $key => $label)
                <option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Показать</button>
    </form>
@endsection

@section('content')
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-green-line">
            <div class="label"><span class="badge-icon tone-green">💰</span> Поступления за период</div>
            <div class="value">{{ number_format($total, 0, ',', ' ') }} ₽</div>
            <div class="hint">Платежей: {{ $payments->total() }}</div>
        </div>
        <div class="card kpi tone-red-line">
            <div class="label"><span class="badge-icon tone-red">⚠️</span> Дебиторка</div>
            <div class="value">{{ number_format($debtTotal, 0, ',', ' ') }} ₽</div>
            <div class="hint"><a href="{{ route('finance.index') }}">К дебиторке →</a></div>
        </div>
        @foreach ($byMethod->take(2) as $row)
            <div class="card kpi tone-accent-line">
                <div class="label"><span class="badge-icon tone-accent">💳</span> {{ $row['label'] }}</div>
                <div class="value">{{ number_format($row['value'], 0, ',', ' ') }} ₽</div>
                <div class="hint">Платежей: {{ $row['count'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid main-side mb-16">
        <div class="card">
            <div class="card-head"><h3>Поступления по дням</h3></div>
            <div class="card-body">
                <div id="cash-chart"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Способы оплаты</h3></div>
            <div class="card-body">
                <div id="method-chart"></div>
            </div>
        </div>
    </div>

    <div class="grid main-side">
        <div class="card">
            <div class="card-head"><h3>Журнал платежей</h3></div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Наряд</th>
                                <th>Клиент</th>
                                <th>Способ</th>
                                <th>Кассир</th>
                                <th class="num">Сумма</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="small">{{ $payment->paid_at->format('d.m.Y H:i') }}</td>
                                    <td><a class="link-plain mono" href="{{ route('orders.show', $payment->order_id) }}">{{ $payment->order?->number }}</a></td>
                                    <td>
                                        <a href="{{ route('customers.show', $payment->customer_id) }}">{{ $payment->customer?->name }}</a>
                                        <div class="small muted">{{ trim(($payment->order?->vehicle?->brand ?? '').' '.($payment->order?->vehicle?->model ?? '')) }}</div>
                                    </td>
                                    <td><span class="badge b-gray">{{ \App\Models\Payment::methodLabel($payment->method) }}</span></td>
                                    <td class="small">{{ $payment->user?->name ?: '—' }}</td>
                                    <td class="num mono"><b>{{ number_format((float) $payment->amount, 0, ',', ' ') }} ₽</b></td>
                                    <td class="right">
                                        <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Отменить платёж?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="empty"><div class="big">💳</div>Платежей за период нет</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.pagination', ['paginator' => $payments])
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Кто принимает оплату</h3></div>
            <div class="card-body tight">
                @forelse ($byCashier as $row)
                    <div style="padding:12px 18px;border-bottom:1px solid #f1f5f9">
                        <div class="flex-between">
                            <b>{{ $row['name'] }}</b>
                            <span class="mono">{{ number_format($row['value'], 0, ',', ' ') }} ₽</span>
                        </div>
                        <div class="bar" style="margin-top:6px">
                            <i style="width:{{ $byCashier->max('value') ? round($row['value'] / $byCashier->max('value') * 100) : 0 }}%"></i>
                        </div>
                        <div class="small muted">{{ $row['count'] }} платежей</div>
                    </div>
                @empty
                    <div class="empty">Нет данных</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        DSH.lineChart('cash-chart', {
            labels: @json(collect($chart)->pluck('label')->values()),
            money: true,
            series: [{ name: 'Приход', color: '#16a34a', data: @json(collect($chart)->pluck('value')->values()) }]
        });

        DSH.donutChart('method-chart', {
            money: true,
            slices: @json($byMethod),
            centerLabel: 'поступлений'
        });
    </script>
@endpush