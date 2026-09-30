@extends('layouts.app')

@section('title', 'Каталог запчастей')
@section('subtitle', 'Позиций: '.$parts->total().' · склад в закупочных ценах: '.number_format((float) $stockValue, 0, ',', ' ').' ₽')

@section('actions')
    <form method="GET" class="flex">
        <input class="input" type="search" name="q" value="{{ $term }}" placeholder="Название, артикул, бренд" style="width:220px">
        <label class="check" style="white-space:nowrap">
            <input type="checkbox" name="low" value="1" @checked($onlyLow)> <span>Только заканчивающиеся ({{ $lowCount }})</span>
        </label>
        <button class="btn" type="submit">Найти</button>
    </form>
    <a class="btn btn-primary" href="{{ route('parts.create') }}">+ Запчасть</a>
@endsection

@section('content')
    @if ($lowCount > 0)
        <div class="alert alert-error">⚠️ {{ $lowCount }} позиций опустились до минимального остатка — их нужно докупить.</div>
    @endif

    <div class="card">
        <div class="card-body tight">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>Артикул / бренд</th>
                            <th>Ячейка</th>
                            <th class="num">Остаток</th>
                            <th class="num">Закупка</th>
                            <th class="num">Продажа</th>
                            <th class="num">Наценка</th>
                            <th style="width:90px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parts as $part)
                            <tr>
                                <td>
                                    <b>{{ $part->name }}</b>
                                    <div class="small muted">{{ $part->category }} · {{ $part->unit }}</div>
                                </td>
                                <td class="small mono">{{ $part->sku ?: '—' }}<div class="muted">{{ $part->brand }}</div></td>
                                <td class="small mono">{{ $part->location ?: '—' }}</td>
                                <td class="num">
                                    @if ($part->isLow())
                                        <span class="badge b-{{ (float) $part->qty <= 0 ? 'red' : 'amber' }}">
                                            {{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}
                                        </span>
                                    @else
                                        <span class="mono">{{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}</span>
                                    @endif
                                </td>
                                <td class="num mono">{{ number_format((float) $part->price, 0, ',', ' ') }} ₽</td>
                                <td class="num mono">{{ number_format((float) $part->sell_price, 0, ',', ' ') }} ₽</td>
                                <td class="num mono">
                                    <span class="badge {{ $part->margin > 0 ? 'b-green' : 'b-gray' }}">
                                        {{ number_format((float) $part->margin, 0, ',', ' ') }} ₽
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-sm" href="{{ route('parts.edit', $part) }}">✎</a>
                                        <form method="POST" action="{{ route('parts.destroy', $part) }}" data-confirm="Удалить позицию из каталога?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="empty"><div class="big">🔩</div>Каталог пуст</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $parts])
        </div>
    </div>
@endsection