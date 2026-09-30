@extends('layouts.app')

@section('title', 'Автомобили')
@section('subtitle', $vehicles->total().' автомобилей в парке клиентов')

@section('actions')
    <form method="GET" class="flex">
        <input class="input" type="search" name="q" value="{{ $term }}" placeholder="Марка, VIN, госномер…" style="width:230px">
        <button class="btn" type="submit">Найти</button>
        @if ($term)
            <a class="btn" href="{{ route('vehicles.index') }}">Сбросить</a>
        @endif
    </form>
    <a class="btn btn-primary" href="{{ route('vehicles.create') }}">+ Автомобиль</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body tight">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Автомобиль</th>
                            <th>Госномер / VIN</th>
                            <th>Владелец</th>
                            <th class="num">Пробег</th>
                            <th>Следующее ТО</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>
                                    <b>{{ $vehicle->brand }} {{ $vehicle->model }}</b>
                                    @if ($vehicle->year) <span class="muted small">{{ $vehicle->year }}</span> @endif
                                    <div class="small muted">{{ collect([$vehicle->color, $vehicle->engine ? $vehicle->engine.' л' : null, $vehicle->transmission])->filter()->join(' · ') }}</div>
                                </td>
                                <td>
                                    <span class="mono">{{ $vehicle->plate ?: '—' }}</span>
                                    <div class="small muted mono">{{ $vehicle->vin }}</div>
                                </td>
                                <td>
                                    <a class="link-plain" href="{{ route('customers.show', $vehicle->customer_id) }}">{{ $vehicle->customer?->name }}</a>
                                    <div class="small muted mono">{{ $vehicle->customer?->phone }}</div>
                                </td>
                                <td class="num mono">{{ number_format($vehicle->mileage, 0, ',', ' ') }} км</td>
                                <td class="small">
                                    @if ($vehicle->next_service_date)
                                        <span class="badge {{ $vehicle->next_service_date->isPast() ? 'b-red' : 'b-gray' }}">{{ $vehicle->next_service_date->format('d.m.Y') }}</span>
                                    @elseif ($vehicle->next_service_mileage)
                                        <span class="badge b-gray">{{ number_format($vehicle->next_service_mileage, 0, ',', ' ') }} км</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-sm" href="{{ route('vehicles.edit', $vehicle) }}">✎</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('orders.create', ['vehicle' => $vehicle->id]) }}">Наряд</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty"><div class="big">🚗</div>Автомобили не найдены</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $vehicles])
        </div>
    </div>
@endsection