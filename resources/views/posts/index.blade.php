@extends('layouts.app')

@section('title', 'Посты')
@section('subtitle', 'Рабочие места и их загрузка')

@section('actions')
    <a class="btn btn-primary" href="{{ route('posts.create') }}">+ Пост</a>
@endsection

@section('content')
    <div class="grid cols-3">
        @forelse ($posts as $row)
            @php $post = $row['post']; $tone = $row['percent'] >= 100 ? 'red' : ($row['percent'] >= 70 ? 'amber' : 'green'); @endphp
            <div class="card">
                <div class="card-head">
                    <h3>{{ $post->name }} @if ($post->code) <span class="badge b-gray">{{ $post->code }}</span> @endif</h3>
                    @if (! $post->is_active)
                        <span class="badge b-gray">неактивен</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="flex-between small muted mb-16">
                        <span>Загрузка</span>
                        <span><b>{{ $row['busy'] }}</b> ч · {{ $row['percent'] }}%</span>
                    </div>
                    <div class="bar {{ $tone }}"><i style="width:{{ min(100, $row['percent']) }}%"></i></div>

                    <dl class="desc mt-16" style="margin-bottom:0">
                        <dt>Ставка</dt>
                        <dd class="mono">{{ number_format((float) $post->hourly_rate, 0, ',', ' ') }} ₽/ч</dd>
                        <dt>Часов в смене</dt>
                        <dd>{{ $post->capacity_hours }} ч</dd>
                        <dt>Нарядов всего</dt>
                        <dd>{{ $post->orders_count }}</dd>
                        <dt>Сотрудников</dt>
                        <dd>{{ $post->employees()->count() }}</dd>
                    </dl>

                    @if ($post->description)
                        <div class="hint-box mt-16">{{ $post->description }}</div>
                    @endif

                    <div class="row-actions mt-16">
                        <a class="btn btn-sm" href="{{ route('posts.edit', $post) }}">✎ Изменить</a>
                        <a class="btn btn-sm" href="{{ route('orders.index', ['post' => $post->id]) }}">Наряды</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" data-confirm="Удалить пост?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="empty"><div class="big">🏗️</div>Посты не настроены</div></div>
        @endforelse
    </div>
@endsection