@extends('layouts.app')

@section('title', $part->exists ? 'Редактирование запчасти' : 'Новая позиция каталога')
@section('subtitle', $part->exists ? $part->name : 'Заведите карточку товара')

@section('actions')
    <a class="btn" href="{{ route('parts.index') }}">← В каталог</a>
@endsection

@section('content')
    <form method="POST" action="{{ $part->exists ? route('parts.update', $part) : route('parts.store') }}">
        @csrf
        @if ($part->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-head"><h3>Карточка товара</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field full">
                        <label for="name">Наименование <span class="req">*</span></label>
                        <input class="input" id="name" name="name" value="{{ old('name', $part->name) }}" required
                               placeholder="Масляный фильтр OEM">
                    </div>
                    <div class="field">
                        <label for="sku">Артикул / код</label>
                        <input class="input" id="sku" name="sku" value="{{ old('sku', $part->sku) }}">
                    </div>
                    <div class="field">
                        <label for="brand">Бренд</label>
                        <input class="input" id="brand" name="brand" value="{{ old('brand', $part->brand) }}">
                    </div>
                    <div class="field">
                        <label for="category">Категория <span class="req">*</span></label>
                        <input class="input" id="category" name="category" value="{{ old('category', $part->category ?: 'Запчасти') }}" required>
                    </div>
                    <div class="field">
                        <label for="unit">Единица измерения <span class="req">*</span></label>
                        <select class="select" id="unit" name="unit">
                            @foreach (['шт', 'л', 'кг', 'м', 'компл'] as $option)
                                <option value="{{ $option }}" @selected(old('unit', $part->unit ?: 'шт') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="price">Закупочная цена, ₽ <span class="req">*</span></label>
                        <input class="input" id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $part->price) }}" required>
                    </div>
                    <div class="field">
                        <label for="sell_price">Цена продажи, ₽ <span class="req">*</span></label>
                        <input class="input" id="sell_price" type="number" step="0.01" min="0" name="sell_price" value="{{ old('sell_price', $part->sell_price) }}" required>
                    </div>
                    <div class="field">
                        <label for="min_stock">Минимальный остаток <span class="req">*</span></label>
                        <input class="input" id="min_stock" type="number" min="0" name="min_stock" value="{{ old('min_stock', $part->min_stock ?? 2) }}" required>
                        <div class="help">Ниже этого уровня позиция попадёт в «заканчиваются»</div>
                    </div>
                    <div class="field">
                        <label for="location">Ячейка склада</label>
                        <input class="input" id="location" name="location" value="{{ old('location', $part->location) }}" placeholder="А-12">
                    </div>
                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $part->exists ? $part->is_active : true))>
                            Позиция активна (доступна для выдачи в наряд)
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ $part->exists ? 'Сохранить' : 'Добавить в каталог' }}</button>
                <a class="btn" href="{{ route('parts.index') }}">Отмена</a>
            </div>
        </div>
    </form>

    @if ($part->exists)
        <div class="hint-box mt-16">
            Текущий остаток: <b>{{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}</b>.
            Изменяйте остаток только через складские операции — так сохранится история движений.
            <a href="{{ route('stock.index', ['part' => $part->id]) }}">Перейти на склад →</a>
        </div>
    @endif
@endsection