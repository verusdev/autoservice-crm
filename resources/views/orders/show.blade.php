@extends('layouts.app')

@section('title', 'Заказ-наряд '.$order->number)
@section('subtitle', $order->customer?->name.' · '.trim(($order->vehicle?->brand ?? '').' '.($order->vehicle?->model ?? '')))

@section('actions')
    <a class="btn" href="{{ route('orders.index') }}">← К нарядам</a>
    <a class="btn" href="{{ route('orders.edit', $order) }}">Редактировать</a>
    <a class="btn" href="{{ route('vehicles.edit', $order->vehicle_id) }}">Автомобиль</a>
@endsection

@section('content')
    <div class="card mb-16">
        <div class="card-body">
            <div class="flex-between flex-wrap mb-16">
                <div>
                    <span class="badge b-{{ \App\Models\Order::statusColor($order->status) }}" style="font-size:13px;padding:5px 12px">
                        <span class="dot"></span>{{ \App\Models\Order::statusLabel($order->status) }}
                    </span>
                    @if ($order->balance > 0)
                        <span class="badge b-red" style="margin-left:6px">Долг {{ number_format($order->balance, 0, ',', ' ') }} ₽</span>
                    @elseif ($order->total > 0)
                        <span class="badge b-green" style="margin-left:6px">Оплачен</span>
                    @endif
                    @if ($order->promised_at)
                        <span class="badge b-{{ $order->promised_at->isPast() && $order->status !== 'issued' ? 'b-red' : 'b-gray' }}" style="margin-left:6px">
                            обещ. {{ $order->promised_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('orders.status', $order) }}" class="flex">
                    @csrf
                    @method('PATCH')
                    <select class="select" name="status" style="width:190px">
                        @foreach (\App\Models\Order::statuses() as $key => $meta)
                            <option value="{{ $key }}" @selected($order->status === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Сменить статус</button>
                </form>
            </div>

            @php $currentStep = \App\Models\Order::statuses()[$order->status]['step'] ?? 0; @endphp
            <div class="steps">
                @foreach (collect(\App\Models\Order::statuses())->where('step', '>', 0)->sortBy('step') as $key => $meta)
                    <div class="step {{ $meta['step'] < $currentStep ? 'done' : ($meta['step'] == $currentStep ? 'current' : '') }}">
                        <div class="n">{{ $meta['step'] < $currentStep ? '✓' : $meta['step'] }}</div>
                        {{ $meta['label'] }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid main-side">
        <div class="stack">
            <!-- Проблема клиента -->
            @if ($order->complaint)
                <div class="alert alert-info">🗣 <b>Жалоба клиента:</b> {{ $order->complaint }}</div>
            @endif

            <!-- РАБОТЫ -->
            <div class="card">
                <div class="card-head">
                    <h3>Работы ({{ $works->count() }})</h3>
                    <span class="small muted">{{ $workSum ? number_format($workSum, 0, ',', ' ') : 0 }} ₽</span>
                </div>
                <div class="card-body tight">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:36px"></th>
                                    <th>Наименование</th>
                                    <th>Мастер</th>
                                    <th class="num">Нормо-часы</th>
                                    <th class="num">Цена</th>
                                    <th style="width:80px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($works as $work)
                                    <tr>
                                        <td class="center">
                                            <form method="POST" action="{{ route('works.toggle', $work) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm" type="submit" title="Отметить выполненной"
                                                    style="width:28px;justify-content:center;padding:4px;background:{{ $work->isDone() ? '#16a34a' : '#f1f5f9' }};color:{{ $work->isDone() ? '#fff' : '#64748b' }};border-color:transparent">
                                                    {{ $work->isDone() ? '✓' : '○' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <span style="{{ $work->isDone() ? '' : 'font-weight:600' }}">{{ $work->name }}</span>
                                            @if ($work->comment)
                                                <div class="small muted">{{ $work->comment }}</div>
                                            @endif
                                        </td>
                                        <td class="small">{{ $work->mechanic?->name ?: '—' }}</td>
                                        <td class="num mono">{{ rtrim(rtrim(number_format((float) $work->norm_hours, 2, ',', ' '), '0'), ',') }}</td>
                                        <td class="num mono">{{ number_format((float) $work->price, 0, ',', ' ') }} ₽</td>
                                        <td class="right">
                                            <form method="POST" action="{{ route('works.destroy', $work) }}"
                                                  data-confirm="Удалить работу из наряда?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty"><div class="big">🔧</div>Работы не добавлены</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <form method="POST" action="{{ route('orders.works.store', $order) }}">
                    @csrf
                    <div class="card-body" style="border-top:1px solid var(--border);background:var(--surface-2)">
                        <div class="form-grid">
                            <div class="field full">
                                <label>Услуга из справочника</label>
                                <select class="select" data-service>
                                    <option value="">— выберите услугу (или введите свою ниже) —</option>
                                    @foreach ($services as $category => $items)
                                        <optgroup label="{{ $category }}">
                                            @foreach ($items as $service)
                                                <option value="{{ $service->id }}"
                                                    data-hours="{{ $service->norm_hours }}"
                                                    data-price="{{ $service->price }}">{{ $service->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Наименование работы</label>
                                <input class="input" name="name" data-work-name placeholder="Замена масла двигателя">
                            </div>
                            <div class="field">
                                <label>Нормо-часы</label>
                                <input class="input" type="number" step="0.1" min="0.1" name="norm_hours" data-work-hours required>
                            </div>
                            <div class="field">
                                <label>Ставка, ₽/ч</label>
                                <input class="input" type="number" step="10" min="0" name="hourly_rate"
                                       value="{{ old('hourly_rate', (float) ($order->post?->hourly_rate ?? 900)) }}">
                            </div>
                            <div class="field">
                                <label>Стоимость, ₽</label>
                                <input class="input" type="number" step="10" min="0" name="price" data-work-price>
                            </div>
                            <div class="field">
                                <label>Мастер</label>
                                <select class="select" name="mechanic_id">
                                    <option value="">Не назначен</option>
                                    @foreach ($mechanics as $mechanic)
                                        <option value="{{ $mechanic->id }}" @selected($order->mechanic_id === $mechanic->id)>
                                            {{ $mechanic->name }} · {{ number_format((float) $mechanic->hourly_rate, 0, ',', ' ') }} ₽/ч
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Комментарий</label>
                                <input class="input" name="comment" placeholder="Пояснение для механика">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-sm" type="submit">+ Добавить работу</button>
                    </div>
                </form>
            </div>

            <!-- ЗАПЧАСТИ -->
            <div class="card">
                <div class="card-head">
                    <h3>Запчасти и материалы ({{ $parts->count() }})</h3>
                    <span class="small muted">{{ $partsSum ? number_format($partsSum, 0, ',', ' ') : 0 }} ₽</span>
                </div>
                <div class="card-body tight">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th>Артикул</th>
                                    <th class="num">Кол-во</th>
                                    <th class="num">Цена</th>
                                    <th class="num">Сумма</th>
                                    <th style="width:80px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($parts as $row)
                                    <tr>
                                        <td>{{ $row->name }}</td>
                                        <td class="small muted mono">{{ $row->sku ?: '—' }}</td>
                                        <td class="num mono">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', ' '), '0'), ',') }}</td>
                                        <td class="num mono">{{ number_format((float) $row->price, 0, ',', ' ') }} ₽</td>
                                        <td class="num mono"><b>{{ number_format($row->sum, 0, ',', ' ') }} ₽</b></td>
                                        <td class="right">
                                            <form method="POST" action="{{ route('order-parts.destroy', $row) }}"
                                                  data-confirm="Удалить позицию и вернуть запчасть на склад?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty"><div class="big">📦</div>Запчасти не добавлены</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <form method="POST" action="{{ route('orders.parts.store', $order) }}">
                    @csrf
                    <div class="card-body" style="border-top:1px solid var(--border);background:var(--surface-2)">
                        <div class="form-grid">
                            <div class="field full">
                                <label>Запчасть со склада</label>
                                <select class="select" name="part_id" data-part required>
                                    <option value="">— выберите запчасть —</option>
                                    @foreach ($catalog as $part)
                                        <option value="{{ $part->id }}"
                                            data-qty="{{ $part->qty }}"
                                            data-price="{{ $part->sell_price }}">
                                            {{ $part->name }} {{ $part->brand ? '('.$part->brand.')' : '' }} — {{ rtrim(rtrim(number_format((float) $part->qty, 2, ',', ' '), '0'), ',') }} {{ $part->unit }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Количество</label>
                                <input class="input" type="number" step="0.1" min="0.1" name="qty" value="1" data-part-qty required>
                            </div>
                            <div class="field">
                                <label>Цена продажи, ₽</label>
                                <input class="input" type="number" step="10" min="0" name="price" data-part-price>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-sm" type="submit">+ Выдать запчасть</button>
                        <span class="right small muted">Остаток на складе уменьшится автоматически</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА -->
        <div class="stack">
            <div class="card">
                <div class="card-head"><h3>Итоги по наряду</h3></div>
                <div class="card-body">
                    <table style="font-size:13.5px">
                        <tr>
                            <td>Работы</td>
                            <td class="num mono">{{ number_format($workSum, 0, ',', ' ') }} ₽</td>
                        </tr>
                        <tr>
                            <td>Запчасти</td>
                            <td class="num mono">{{ number_format($partsSum, 0, ',', ' ') }} ₽</td>
                        </tr>
                        <tr>
                            <td class="muted">Скидка</td>
                            <td class="num mono">−{{ number_format((float) $order->discount, 0, ',', ' ') }}%</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);font-weight:700">
                            <td>К оплате</td>
                            <td class="num mono">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</td>
                        </tr>
                        <tr>
                            <td class="muted">Оплачено</td>
                            <td class="num mono">{{ number_format((float) $order->paid, 0, ',', ' ') }} ₽</td>
                        </tr>
                        <tr style="font-weight:700">
                            <td>{{ $order->balance > 0 ? 'Остаток долга' : 'Переплата' }}</td>
                            <td class="num">
                                <span class="badge {{ $order->balance > 0 ? 'b-red' : 'b-green' }}">
                                    {{ number_format(abs($order->balance), 0, ',', ' ') }} ₽
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div class="mt-16">
                        <div class="flex-between small muted mb-16">
                            <span>Готовность работ</span><span>{{ $order->progress() }}%</span>
                        </div>
                        <div class="bar {{ $order->progress() === 100 ? 'green' : '' }}"><i style="width:{{ $order->progress() }}%"></i></div>
                        <div class="small muted mt-16">
                            Нормо-часов: {{ rtrim(rtrim(number_format((float) $order->works->sum('norm_hours'), 2, ',', ' '), '0'), ',') }} ч
                        </div>
                    </div>
                </div>
            </div>

            @if ($order->total > 0)
                <div class="card">
                    <div class="card-head"><h3>Приём оплаты</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('orders.payments.store', $order) }}" class="stack" style="gap:12px">
                            @csrf
                            <div class="field">
                                <label>Сумма, ₽</label>
                                <input class="input" type="number" step="10" min="1" name="amount"
                                       value="{{ old('amount', $order->balance > 0 ? round($order->balance, 2) : round((float) $order->total, 2)) }}" required data-money>
                            </div>
                            <div class="field">
                                <label>Способ оплаты</label>
                                <select class="select" name="method">
                                    @foreach ($payments as $key => $label)
                                        <option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Дата</label>
                                <input class="input" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">Принять оплату</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($order->payments->isNotEmpty())
                <div class="card">
                    <div class="card-head"><h3>История оплат</h3></div>
                    <div class="card-body tight">
                        @foreach ($order->payments as $payment)
                            <div style="padding:11px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:10px">
                                <div>
                                    <b class="mono">{{ number_format((float) $payment->amount, 0, ',', ' ') }} ₽</b>
                                    <div class="small muted">
                                        {{ \App\Models\Payment::methodLabel($payment->method) }} · {{ $payment->paid_at->format('d.m.Y H:i') }}
                                        @if ($payment->user) · {{ $payment->user->name }} @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Отменить платёж?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost-danger" type="submit">✕</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-head"><h3>Информация</h3></div>
                <div class="card-body">
                    <dl class="desc" style="margin:0">
                        <dt>Клиент</dt>
                        <dd><a href="{{ route('customers.show', $order->customer_id) }}">{{ $order->customer?->name }}</a></dd>
                        <dt>Телефон</dt>
                        <dd class="mono">{{ $order->customer?->phone }}</dd>
                        <dt>Автомобиль</dt>
                        <dd>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</dd>
                        <dt>Пробег</dt>
                        <dd class="mono">{{ number_format($order->mileage, 0, ',', ' ') }} км</dd>
                        <dt>Приёмка</dt>
                        <dd>{{ $order->intake_at->format('d.m.Y H:i') }}</dd>
                        @if ($order->closed_at)
                            <dt>Выдан</dt>
                            <dd>{{ $order->closed_at->format('d.m.Y H:i') }}</dd>
                        @endif
                        <dt>Пост</dt>
                        <dd>{{ $order->post?->name ?: '—' }}</dd>
                        <dt>Мастер</dt>
                        <dd>{{ $order->mechanic?->name ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($order->status !== 'issued')
                <form method="POST" action="{{ route('orders.destroy', $order) }}"
                      data-confirm="Удалить наряд? Запчасти вернутся на склад.">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost-danger btn-block" type="submit">Удалить заказ-наряд</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const service = document.querySelector('[data-service]');
            const workName = document.querySelector('[data-work-name]');
            const workHours = document.querySelector('[data-work-hours]');
            const workPrice = document.querySelector('[data-work-price]');

            if (service) {
                service.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    if (!this.value) return;
                    workName.value = opt.textContent.trim();
                    workHours.value = opt.dataset.hours;
                    workPrice.value = opt.dataset.price;
                });
            }

            const part = document.querySelector('[data-part]');
            const partQty = document.querySelector('[data-part-qty]');
            const partPrice = document.querySelector('[data-part-price]');

            if (part) {
                part.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    if (!this.value) return;
                    partPrice.value = opt.dataset.price;
                    partQty.max = opt.dataset.qty;
                    partQty.title = 'Доступно: ' + opt.dataset.qty;
                });
            }
        })();
    </script>
@endpush