@extends('layouts.app')

@section('title', $customer->exists ? 'Редактирование клиента' : 'Новый клиент')
@section('subtitle', $customer->exists ? $customer->name : 'Заполните карточку владельца')

@section('actions')
    <a class="btn" href="{{ route('customers.index') }}">← К списку</a>
@endsection

@section('content')
    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">
        @csrf
        @if ($customer->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-head"><h3>Основные данные</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">ФИО / название <span class="req">*</span></label>
                        <input class="input" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Телефон <span class="req">*</span></label>
                        <input class="input" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required placeholder="+7 900 000-00-00">
                    </div>
                    <div class="field">
                        <label for="phone2">Дополнительный телефон</label>
                        <input class="input" id="phone2" name="phone2" value="{{ old('phone2', $customer->phone2) }}">
                    </div>
                    <div class="field">
                        <label for="email">E-mail</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email', $customer->email) }}">
                    </div>
                    <div class="field">
                        <label for="type">Тип клиента</label>
                        <select class="select" id="type" name="type">
                            @foreach (\App\Models\Customer::types() as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $customer->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="company">Компания / ИП</label>
                        <input class="input" id="company" name="company" value="{{ old('company', $customer->company) }}">
                    </div>
                    <div class="field">
                        <label for="source">Источник</label>
                        <input class="input" id="source" name="source" value="{{ old('source', $customer->source) }}" placeholder="2ГИС, рекомендация, сайт…">
                    </div>
                    <div class="field">
                        <label for="birth_date">Дата рождения</label>
                        <input class="input" id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="field full">
                        <label for="address">Адрес</label>
                        <input class="input" id="address" name="address" value="{{ old('address', $customer->address) }}">
                    </div>
                    <div class="field">
                        <label for="discount">Персональная скидка, %</label>
                        <input class="input" id="discount" type="number" name="discount" min="0" max="100" value="{{ old('discount', $customer->discount ?? 0) }}">
                        <div class="help">Применяется к новым нарядам автоматически</div>
                    </div>
                    <div class="field full">
                        <label for="notes">Заметки</label>
                        <textarea class="input" id="notes" name="notes">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ $customer->exists ? 'Сохранить' : 'Добавить клиента' }}</button>
                <a class="btn" href="{{ route('customers.index') }}">Отмена</a>
                @if ($customer->exists)
                    <div class="right">
                        <a class="btn btn-primary" href="{{ route('orders.create', ['customer' => $customer->id]) }}">Создать наряд</a>
                    </div>
                @endif
            </div>
        </div>
    </form>

    @if ($customer->exists)
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="mt-16"
              data-confirm="Удалить клиента безвозвратно?">
            @csrf
            @method('DELETE')
            <button class="btn btn-ghost-danger btn-sm" type="submit">Удалить клиента</button>
        </form>
    @endif
@endsection