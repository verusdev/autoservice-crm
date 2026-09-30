@extends('layouts.app')

@section('title', $employee->exists ? 'Редактирование сотрудника' : 'Новый сотрудник')
@section('subtitle', $employee->exists ? $employee->name : 'Учётная запись для входа в систему')

@section('actions')
    <a class="btn" href="{{ route('employees.index') }}">← К сотрудникам</a>
@endsection

@section('content')
    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if ($employee->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-head"><h3>Данные сотрудника</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">ФИО <span class="req">*</span></label>
                        <input class="input" id="name" name="name" value="{{ old('name', $employee->name) }}" required>
                    </div>
                    <div class="field">
                        <label for="email">E-mail (логин) <span class="req">*</span></label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Телефон</label>
                        <input class="input" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
                    </div>
                    <div class="field">
                        <label for="position">Должность</label>
                        <input class="input" id="position" name="position" value="{{ old('position', $employee->position) }}" placeholder="Автослесарь 5 разряда">
                    </div>
                    <div class="field">
                        <label for="role">Роль в системе <span class="req">*</span></label>
                        <select class="select" id="role" name="role" required>
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}" @selected(old('role', $employee->role) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="post_id">Закреплённый пост</label>
                        <select class="select" id="post_id" name="post_id">
                            <option value="">Не закреплён</option>
                            @foreach ($posts as $post)
                                <option value="{{ $post->id }}" @selected(old('post_id', $employee->post_id) == $post->id)>{{ $post->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="hourly_rate">Ставка, ₽/час</label>
                        <input class="input" id="hourly_rate" type="number" step="10" min="0" name="hourly_rate"
                               value="{{ old('hourly_rate', (float) ($employee->hourly_rate ?? 900)) }}">
                        <div class="help">Используется для расчёта себестоимости и маржи</div>
                    </div>
                    <div class="field">
                        <label for="password">Пароль {{ $employee->exists ? '(оставьте пустым, чтобы не менять)' : '' }}</label>
                        <input class="input" id="password" type="password" name="password" @required(! $employee->exists) minlength="6">
                    </div>
                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->exists ? $employee->is_active : true))>
                            Сотрудник работает в сервисе
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ $employee->exists ? 'Сохранить' : 'Добавить сотрудника' }}</button>
                <a class="btn" href="{{ route('employees.index') }}">Отмена</a>
            </div>
        </div>
    </form>
@endsection