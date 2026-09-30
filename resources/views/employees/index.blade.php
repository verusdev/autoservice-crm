@extends('layouts.app')

@section('title', 'Сотрудники')
@section('subtitle', 'Мастера, приёмщики и руководство сервиса')

@section('actions')
    <a class="btn btn-primary" href="{{ route('employees.create') }}">+ Сотрудник</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body tight">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Сотрудник</th>
                            <th>Роль</th>
                            <th>Пост</th>
                            <th>Ставка</th>
                            <th class="num">Нормо-часы</th>
                            <th class="num">Выработка</th>
                            <th class="num">Маржа</th>
                            <th style="width:150px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $row)
                            @php $employee = $row['user']; @endphp
                            <tr>
                                <td>
                                    <div class="flex" style="gap:9px">
                                        <span class="avatar">{{ $employee->initials() }}</span>
                                        <div>
                                            <b>{{ $employee->name }}</b>
                                            <div class="small muted">
                                                {{ $employee->position ?: \App\Models\User::roleLabel($employee->role) }}
                                                @if (! $employee->is_active) · <span style="color:var(--red)">не активен</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge {{ $employee->role === 'admin' ? 'b-violet' : ($employee->role === 'advisor' ? 'b-blue' : 'b-gray') }}">
                                    {{ \App\Models\User::roleLabel($employee->role) }}</span></td>
                                <td class="small">{{ $employee->post?->name ?: '—' }}</td>
                                <td class="num mono">{{ number_format((float) $employee->hourly_rate, 0, ',', ' ') }} ₽/ч</td>
                                <td class="num mono">{{ number_format($row['hours'], 1, ',', ' ') }} ч</td>
                                <td class="num mono">{{ number_format($row['revenue'], 0, ',', ' ') }} ₽</td>
                                <td class="num mono">
                                    <span class="badge {{ $row['profit'] >= 0 ? 'b-green' : 'b-red' }}">
                                        {{ number_format($row['profit'], 0, ',', ' ') }} ₽
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-sm" href="{{ route('employees.edit', $employee) }}">✎</a>
                                        @if ($employee->id !== auth()->id())
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                                  data-confirm="Деактивировать сотрудника?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="empty"><div class="big">👷</div>Сотрудники не добавлены</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection