@extends('layouts.app')

@php($ownerId = $vehicle->customer_id ?: ($selectedCustomer ?? 0))

@section('title', $vehicle->exists ? 'Редактирование автомобиля' : 'Новый автомобиль')
@section('subtitle', $vehicle->exists ? $vehicle->title : 'Привяжите автомобиль к владельцу')

@section('actions')
    @if ($ownerId)
        <a class="btn" href="{{ route('customers.show', $ownerId) }}">← К клиенту</a>
    @endif
@endsection

@section('content')
    <form method="POST" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}">
        @csrf
        @if ($vehicle->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-head"><h3>Данные автомобиля</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field full">
                        <label for="customer_id">Владелец <span class="req">*</span></label>
                        <select class="select" id="customer_id" name="customer_id" required>
                            <option value="">— выберите клиента —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $selectedCustomer ?? $vehicle->customer_id) == $customer->id)>
                                    {{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="brand">Марка <span class="req">*</span></label>
                        <input class="input" id="brand" name="brand" value="{{ old('brand', $vehicle->brand) }}" required placeholder="Toyota">
                    </div>
                    <div class="field">
                        <label for="model">Модель <span class="req">*</span></label>
                        <input class="input" id="model" name="model" value="{{ old('model', $vehicle->model) }}" required placeholder="Camry">
                    </div>
                    <div class="field">
                        <label for="year">Год выпуска</label>
                        <input class="input" id="year" type="number" name="year" min="1950" max="{{ date('Y') + 2 }}" value="{{ old('year', $vehicle->year) }}">
                    </div>
                    <div class="field">
                        <label for="plate">Госномер</label>
                        <input class="input" id="plate" name="plate" value="{{ old('plate', $vehicle->plate) }}" placeholder="А123ВС77" style="text-transform:uppercase">
                    </div>
                    <div class="field">
                        <label for="vin">VIN</label>
                        <input class="input" id="vin" name="vin" value="{{ old('vin', $vehicle->vin) }}" style="text-transform:uppercase">
                    </div>
                    <div class="field">
                        <label for="color">Цвет</label>
                        <input class="input" id="color" name="color" value="{{ old('color', $vehicle->color) }}">
                    </div>
                    <div class="field">
                        <label for="engine">Объём двигателя, л</label>
                        <input class="input" id="engine" type="number" step="0.1" name="engine" value="{{ old('engine', $vehicle->engine) }}">
                    </div>
                    <div class="field">
                        <label for="transmission">Коробка</label>
                        <select class="select" id="transmission" name="transmission">
                            @foreach (['', 'Механика', 'Автомат', 'Робот', 'Вариатор'] as $option)
                                <option value="{{ $option }}" @selected(old('transmission', $vehicle->transmission) === $option)>{{ $option ?: '— не указана —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="fuel">Топливо</label>
                        <select class="select" id="fuel" name="fuel">
                            @foreach (['', 'Бензин', 'Дизель', 'Газ', 'Гибрид', 'Электро'] as $option)
                                <option value="{{ $option }}" @selected(old('fuel', $vehicle->fuel) === $option)>{{ $option ?: '— не указано —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="mileage">Пробег, км</label>
                        <input class="input" id="mileage" type="number" name="mileage" min="0" value="{{ old('mileage', $vehicle->mileage ?? 0) }}">
                    </div>
                    <div class="field">
                        <label for="next_service_date">Следующее ТО — дата</label>
                        <input class="input" id="next_service_date" type="date" name="next_service_date" value="{{ old('next_service_date', $vehicle->next_service_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="field">
                        <label for="next_service_mileage">Следующее ТО — пробег, км</label>
                        <input class="input" id="next_service_mileage" type="number" name="next_service_mileage" min="0" value="{{ old('next_service_mileage', $vehicle->next_service_mileage) }}">
                    </div>
                    <div class="field full">
                        <label for="note">Особенности и примечания</label>
                        <textarea class="input" id="note" name="note">{{ old('note', $vehicle->note) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ $vehicle->exists ? 'Сохранить' : 'Добавить автомобиль' }}</button>
                <a class="btn" href="{{ $ownerId ? route('customers.show', $ownerId) : route('vehicles.index') }}">Отмена</a>
            </div>
        </div>
    </form>
@endsection