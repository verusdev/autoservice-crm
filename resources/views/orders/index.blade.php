@extends('layouts.app')

@section('title', 'Заказ-наряды')
@section('subtitle', 'Всего '.$summary->cnt.' нарядов · на '.number_format((float) $summary->total, 0, ',', ' ').' ₽')

@section('actions')
    <a class="btn btn-primary" href="{{ route('orders.create') }}">+ Новый наряд</a>
@endsection

@section('content')
    <div class="card mb-16">
        <div class="card-body">
            <form method="GET" class="filters">
                <div class="field">
                    <label>Поиск</label>
                    <input class="input" type="search" name="q" value="{{ $term }}" placeholder="Номер, клиент, авто, VIN">
                </div>
                <div class="field">
                    <label>Статус</label>
                    <select class="select" name="status" data-autosubmit>
                        <option value="">Все</option>
                        @foreach (\App\Models\Order::statuses() as $key => $meta)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Пост</label>
                    <select class="select" name="post" data-autosubmit>
                        <option value="">Все</option>
                        @foreach ($posts as $post)
                            <option value="{{ $post->id }}" @selected(request('post') == $post->id)>{{ $post->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>С</label>
                    <input class="input" type="date" name="from" value="{{ request('from') }}" data-autosubmit>
                </div>
                <div class="field">
                    <label>По</label>
                    <input class="input" type="date" name="to" value="{{ request('to') }}" data-autosubmit>
                </div>
                <button class="btn btn-primary" type="submit">Применить</button>
                <a class="btn" href="{{ route('orders.index') }}">Сбросить</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body tight">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Клиент / авто</th>
                            <th>Дата</th>
                            <th>Пост / мастер</th>
                            <th>Статус</th>
                            <th class="num">Итого</th>
                            <th class="num">Оплата</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>
                                    <a class="link-plain mono" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a>
                                    <div class="small muted">{{ $order->intake_at->format('d.m.Y H:i') }}</div>
                                </td>
                                <td>
                                    <div>{{ $order->customer?->name }}</div>
                                    <div class="small muted">{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }} @if ($order->vehicle?->plate) · {{ $order->vehicle->plate }} @endif</div>
                                </td>
                                <td class="small">
                                    {{ $order->intake_at->format('d.m.Y') }}
                                    @if ($order->promised_at)
                                        <div class="muted">обещ. {{ $order->promised_at->format('d.m') }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $order->post?->name ?: '—' }}
                                    <div class="muted">{{ $order->mechanic?->name ?: '' }}</div>
                                </td>
                                <td>
                                    <span class="badge b-{{ \App\Models\Order::statusColor($order->status) }}">
                                        <span class="dot"></span>{{ \App\Models\Order::statusLabel($order->status) }}
                                    </span>
                                    @if ($order->progress() > 0 && $order->progress() < 100)
                                        <div class="bar mt-0" style="margin-top:5px;width:90px"><i style="width:{{ $order->progress() }}%"></i></div>
                                    @endif
                                </td>
                                <td class="num mono">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</td>
                                <td class="num mono">
                                    @if ($order->balance > 0)
                                        <span class="badge b-amber">{{ number_format((float) $order->paid, 0, ',', ' ') }} / {{ number_format($order->balance, 0, ',', ' ') }}</span>
                                    @else
                                        <span class="badge b-green">оплачен</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty"><div class="big">📋</div>Заказ-наряды не найдены</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $orders])
        </div>
    </div>
@endsection