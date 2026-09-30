@extends('layouts.app')

@section('title', 'Складские операции')
@section('subtitle', 'Приход, расход и инвентаризация')

@section('actions')
    <a class="btn" href="{{ route('parts.index') }}">Каталог запчастей</a>
@endsection

@section('content')
    <div class="grid side-main mb-16">
        <div class="card">
            <div class="card-head"><h3>Движения по складу</h3></div>
            <div class="card-body">
                <form method="GET" class="filters mb-16">
                    <div class="field">
                        <label>Операция</label>
                        <select class="select" name="type" data-autosubmit>
                            <option value="">Все</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Запчасть</label>
                        <select class="select" name="part" data-autosubmit>
                            <option value="">Все</option>
                            @foreach ($parts as $part)
                                <option value="{{ $part->id }}" @selected(request('part') == $part->id)>{{ $part->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a class="btn" href="{{ route('stock.index') }}">Сбросить</a>
                </form>
            </div>
            <div class="card-body tight">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Тип</th>
                                <th>Запчасть</th>
                                <th class="num">Кол-во</th>
                                <th>Наряд / примечание</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="small">{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $movement->type === 'in' ? 'b-green' : ($movement->type === 'out' ? 'b-amber' : 'b-blue') }}">
                                            {{ $types[$movement->type] }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="link-plain" href="{{ route('parts.edit', $movement->part_id) }}">{{ $movement->part?->name }}</a>
                                        <div class="small muted mono">{{ $movement->part?->sku }}</div>
                                    </td>
                                    <td class="num mono">{{ $movement->signedQty() > 0 ? '+' : '' }}{{ number_format($movement->signedQty(), 2, ',', ' ') }}</td>
                                    <td class="small">
                                        @if ($movement->order)
                                            <a class="link-plain mono" href="{{ route('orders.show', $movement->order_id) }}">{{ $movement->order->number }}</a>
                                        @endif
                                        {{ $movement->note }}
                                    </td>
                                    <td class="right">
                                        <form method="POST" action="{{ route('stock.destroy', $movement) }}" data-confirm="Отменить движение? Остаток будет пересчитан.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="empty"><div class="big">📦</div>Движений пока нет</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.pagination', ['paginator' => $movements])
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h3>Новая операция</h3></div>
                <form method="POST" action="{{ route('stock.store') }}">
                    @csrf
                    <div class="card-body stack" style="gap:12px">
                        <div class="field">
                            <label>Операция</label>
                            <select class="select" name="type" required>
                                <option value="in">Приход от поставщика</option>
                                <option value="out">Расход (списание, брак)</option>
                                <option value="adjust">Инвентаризация (факт)</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Запчасть <span class="req">*</span></label>
                            <select class="select" name="part_id" required>
                                <option value="">— выберите —</option>
                                @foreach ($parts as $part)
                                    <option value="{{ $part->id }}" data-qty="{{ $part->qty }}" @selected(request('part') == $part->id)>
                                        {{ $part->name }} — сейчас {{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Количество <span class="req">*</span></label>
                            <input class="input" type="number" step="0.01" min="0.01" name="qty" required>
                            <div class="help">Для инвентаризации введите фактический остаток</div>
                        </div>
                        <div class="field">
                            <label>Цена, ₽</label>
                            <input class="input" type="number" step="0.01" min="0" name="price" data-money>
                        </div>
                        <div class="field">
                            <label>Примечание</label>
                            <input class="input" name="note" placeholder="Поставщик, накладная №…">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Провести</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-head"><h3>Оборот склада</h3></div>
                <div class="card-body">
                    <dl class="desc" style="margin:0">
                        <dt>Приход всего</dt>
                        <dd>{{ number_format((float) $totals['in_qty'], 0, ',', ' ') }} ед.</dd>
                        <dt>Списано в заказы</dt>
                        <dd>{{ number_format((float) $totals['out_qty'], 0, ',', ' ') }} ед. на {{ number_format((float) $totals['out_sum'], 0, ',', ' ') }} ₽</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection