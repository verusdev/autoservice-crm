@extends('layouts.app')

@section('title', $customer->name)
@section('subtitle', 'Клиент с '.now()->format('d.m.Y').' · телефон '.$customer->phone)

@section('actions')
    <a class="btn" href="{{ route('customers.index') }}">← Клиенты</a>
    <a class="btn" href="{{ route('customers.edit', $customer) }}">Редактировать</a>
    <a class="btn btn-primary" href="{{ route('orders.create', ['customer' => $customer->id]) }}">+ Новый наряд</a>
@endsection

@section('content')
    <div class="grid cols-4 mb-16">
        <div class="card kpi tone-line">
            <div class="label">Оборот</div>
            <div class="value">{{ number_format($stats['total'], 0, ',', ' ') }} ₽</div>
            <div class="hint">За всё время</div>
        </div>
        <div class="card kpi tone-green-line">
            <div class="label">Оплачено</div>
            <div class="value">{{ number_format($stats['paid'], 0, ',', ' ') }} ₽</div>
            <div class="hint">{{ $stats['orders'] }} наряд(ов)</div>
        </div>
        <div class="card kpi tone-red-line">
            <div class="label">Долг</div>
            <div class="value">{{ number_format($stats['debt'], 0, ',', ' ') }} ₽</div>
            <div class="hint">{{ $stats['debt'] > 0 ? 'Требует оплаты' : 'Задолженности нет' }}</div>
        </div>
        <div class="card kpi tone-violet-line">
            <div class="label">Автомобили</div>
            <div class="value">{{ $stats['vehicles'] }}</div>
            <div class="hint">{{ $customer->discount ? 'Скидка '.$customer->discount.'%' : 'Без персональной скидки' }}</div>
        </div>
    </div>

    <div class="grid main-side">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h3>Автомобили на обслуживании</h3>
                    <a class="btn btn-sm btn-primary" href="{{ route('vehicles.create', ['customer' => $customer->id]) }}">+ Автомобиль</a>
                </div>
                <div class="card-body tight">
                    @forelse ($customer->vehicles as $vehicle)
                        <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9">
                            <div class="flex-between">
                                <div>
                                    <b>{{ $vehicle->brand }} {{ $vehicle->model }}</b>
                                    @if ($vehicle->year) <span class="muted">{{ $vehicle->year }} г.</span> @endif
                                    @if ($vehicle->plate) <span class="badge b-blue" style="margin-left:6px">{{ $vehicle->plate }}</span> @endif
                                </div>
                                <div class="row-actions">
                                    <span class="small muted mono">{{ number_format($vehicle->mileage, 0, ',', ' ') }} км</span>
                                    <a class="btn btn-sm" href="{{ route('vehicles.edit', $vehicle) }}">✎</a>
                                    <a class="btn btn-sm btn-primary" href="{{ route('orders.create', ['vehicle' => $vehicle->id]) }}">Наряд</a>
                                </div>
                            </div>
                            <div class="small muted mt-0">
                                {{ collect([$vehicle->color, $vehicle->engine ? $vehicle->engine.' л' : null, $vehicle->transmission, $vehicle->fuel, $vehicle->vin])->filter()->join(' · ') }}
                            </div>
                            @if ($vehicle->next_service_date || $vehicle->next_service_mileage)
                                <div class="small" style="margin-top:6px">
                                    <span class="badge {{ $vehicle->next_service_date?->isPast() ? 'b-red' : 'b-gray' }} badge-icon">
                                        ТО: {{ $vehicle->next_service_date?->format('d.m.Y') ?? 'по пробегу' }}
                                        @if ($vehicle->next_service_mileage) — {{ number_format($vehicle->next_service_mileage, 0, ',', ' ') }} км @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="empty"><div class="big">🚗</div>Автомобили не добавлены</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>История заказ-нарядов</h3></div>
                <div class="card-body tight">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Автомобиль</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                    <th class="num">Итого</th>
                                    <th class="num">Оплачено</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td><a class="link-plain mono" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                                        <td>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</td>
                                        <td class="small muted">{{ $order->intake_at->format('d.m.Y') }}</td>
                                        <td>
                                            <span class="badge b-{{ \App\Models\Order::statusColor($order->status) }}">
                                                {{ \App\Models\Order::statusLabel($order->status) }}
                                            </span>
                                        </td>
                                        <td class="num mono">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</td>
                                        <td class="num mono">
                                            @if ($order->balance > 0)
                                                <span class="badge b-red">−{{ number_format($order->balance, 0, ',', ' ') }}</span>
                                            @else
                                                <span class="badge b-green">оплачен</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty"><div class="big">📋</div>Нарядов пока нет</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Карточка клиента</h3></div>
            <div class="card-body">
                <dl class="desc" style="margin:0">
                    <dt>Тип</dt>
                    <dd>{{ \App\Models\Customer::types()[$customer->type] ?? $customer->type }}</dd>
                    <dt>Телефон</dt>
                    <dd class="mono">{{ $customer->phone }}</dd>
                    @if ($customer->phone2)
                        <dt>Доп. телефон</dt>
                        <dd class="mono">{{ $customer->phone2 }}</dd>
                    @endif
                    @if ($customer->email)
                        <dt>E-mail</dt>
                        <dd>{{ $customer->email }}</dd>
                    @endif
                    @if ($customer->company)
                        <dt>Компания</dt>
                        <dd>{{ $customer->company }}</dd>
                    @endif
                    @if ($customer->source)
                        <dt>Источник</dt>
                        <dd>{{ $customer->source }}</dd>
                    @endif
                    @if ($customer->address)
                        <dt>Адрес</dt>
                        <dd>{{ $customer->address }}</dd>
                    @endif
                    @if ($customer->birth_date)
                        <dt>День рождения</dt>
                        <dd>{{ $customer->birth_date->format('d.m.Y') }}</dd>
                    @endif
                    <dt>Скидка</dt>
                    <dd>{{ $customer->discount }}%</dd>
                    <dt>Клиент с</dt>
                    <dd>{{ $customer->created_at->format('d.m.Y') }}</dd>
                </dl>

                @if ($customer->notes)
                    <div class="hint-box mt-16">{{ $customer->notes }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection