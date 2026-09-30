@extends('layouts.app')

@section('title', 'Клиенты')
@section('subtitle', $customers->total().' записей в базе')

@section('actions')
    <form method="GET" class="flex">
        <input class="input" type="search" name="q" value="{{ $term }}" placeholder="Имя, телефон, e-mail…" style="width:230px">
        <button class="btn" type="submit">Найти</button>
        @if ($term)
            <a class="btn" href="{{ route('customers.index') }}">Сбросить</a>
        @endif
    </form>
    <a class="btn btn-primary" href="{{ route('customers.create') }}">+ Клиент</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body tight">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th class="num">Авто</th>
                            <th class="num">Нарядов</th>
                            <th class="num">Оборот</th>
                            <th class="num">Долг</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>
                                    <a class="link-plain" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                                    @if ($customer->type === 'company')
                                        <span class="badge b-violet" style="margin-left:6px">{{ $customer->company ?: 'Юр. лицо' }}</span>
                                    @endif
                                    @if ($customer->discount)
                                        <span class="badge b-amber" style="margin-left:4px">скидка {{ $customer->discount }}%</span>
                                    @endif
                                </td>
                                <td class="mono">{{ $customer->phone }}</td>
                                <td class="num">{{ $customer->vehicles_count }}</td>
                                <td class="num">{{ $customer->orders_count }}</td>
                                <td class="num mono">{{ number_format((float) $customer->turnover, 0, ',', ' ') }} ₽</td>
                                <td class="num mono">
                                    @if ((float) $customer->debt > 0)
                                        <span class="badge b-red">{{ number_format((float) $customer->debt, 0, ',', ' ') }} ₽</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-sm" href="{{ route('vehicles.create', ['customer' => $customer->id]) }}">+ Авто</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('orders.create', ['customer' => $customer->id]) }}">Наряд</a>
                                        <a class="btn btn-sm" href="{{ route('customers.edit', $customer) }}">✎</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">
                                <div class="empty"><div class="big">👤</div>Клиенты не найдены</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $customers])
        </div>
    </div>
@endsection