@extends('layouts.app')

@section('title', $post->exists ? 'Редактирование поста' : 'Новый пост')
@section('subtitle', $post->exists ? $post->name : 'Рабочее место сервиса')

@section('actions')
    <a class="btn" href="{{ route('posts.index') }}">← К постам</a>
@endsection

@section('content')
    <form method="POST" action="{{ $post->exists ? route('posts.update', $post) : route('posts.store') }}">
        @csrf
        @if ($post->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-head"><h3>Параметры поста</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Название <span class="req">*</span></label>
                        <input class="input" id="name" name="name" value="{{ old('name', $post->name) }}" required placeholder="Слесарный пост 1">
                    </div>
                    <div class="field">
                        <label for="code">Код</label>
                        <input class="input" id="code" name="code" value="{{ old('code', $post->code) }}" placeholder="П-1">
                    </div>
                    <div class="field">
                        <label for="capacity_hours">Часов в смене <span class="req">*</span></label>
                        <input class="input" id="capacity_hours" type="number" min="1" max="24" name="capacity_hours"
                               value="{{ old('capacity_hours', $post->capacity_hours ?? 8) }}" required>
                        <div class="help">Используется для расчёта загрузки поста</div>
                    </div>
                    <div class="field">
                        <label for="hourly_rate">Базовая ставка, ₽/час <span class="req">*</span></label>
                        <input class="input" id="hourly_rate" type="number" step="10" min="0" name="hourly_rate"
                               value="{{ old('hourly_rate', (float) ($post->hourly_rate ?? 900)) }}" required>
                    </div>
                    <div class="field full">
                        <label for="description">Описание / специализация</label>
                        <input class="input" id="description" name="description" value="{{ old('description', $post->description) }}"
                               placeholder="Ходовая, подвеска, сцепление">
                    </div>
                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $post->exists ? $post->is_active : true))>
                            Пост принимает наряды
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ $post->exists ? 'Сохранить' : 'Добавить пост' }}</button>
                <a class="btn" href="{{ route('posts.index') }}">Отмена</a>
            </div>
        </div>
    </form>
@endsection