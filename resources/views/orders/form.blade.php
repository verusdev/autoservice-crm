@extends('layouts.app')

@section('title', $order->exists ? 'Редактирование '.$order->number : 'Новый заказ-наряд')
@section('subtitle', $order->exists ? 'Изменение параметров приёмки' : 'Приём автомобиля в сервис')

@section('actions')
    <a class="btn" href="{{ route('orders.index') }}">← К нарядам</a>
    @if ($order->exists)
        <a class="btn btn-primary" href="{{ route('orders.show', $order) }}">К наряду</a>
    @endif
@endsection

@section('content')
    <form method="POST" action="{{ $order->exists ? route('orders.update', $order) : route('orders.store') }}">
        @csrf
        @if ($order->exists)
            @method('PUT')
        @endif

        <div class="grid main-side">
            <div class="card">
                <div class="card-head"><h3>Данные приёмки</h3></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label for="customer_id">Клиент <span class="req">*</span></label>
                            <select class="select" id="customer_id" name="customer_id" required data-customer>
                                <option value="">— выберите клиента —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        @selected(old('customer_id', $selectedCustomer ?? $order->customer_id) == $customer->id)>
                                        {{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="vehicle_id">Автомобиль <span class="req">*</span></label>
                            <select class="select" id="vehicle_id" name="vehicle_id" required data-vehicle>
                                <option value="">— сначала выберите клиента —</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" data-customer-id="{{ $vehicle->customer_id }}"
                                        @selected(old('vehicle_id', $selectedVehicle ?? $order->vehicle_id) == $vehicle->id)>
                                        {{ $vehicle->brand }} {{ $vehicle->model }}{{ $vehicle->plate ? ' · '.$vehicle->plate : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="help">
                                Автомобиля нет? <a href="{{ route('vehicles.create', ['customer' => $selectedCustomer ?? $order->customer_id]) }}">Добавить</a>
                            </div>
                        </div>

                        <div class="field">
                            <label for="mileage">Пробег, км <span class="req">*</span></label>
                            <input class="input" id="mileage" type="number" name="mileage" min="0"
                                   value="{{ old('mileage', $order->mileage ?? 0) }}" required data-mileage>
                        </div>

                        <div class="field">
                            <label for="post_id">Пост</label>
                            <select class="select" id="post_id" name="post_id" data-autosubmit>
                                <option value="">Не назначен</option>
                                @foreach ($posts as $post)
                                    <option value="{{ $post->id }}" @selected(old('post_id', $order->post_id) == $post->id)>
                                        {{ $post->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="mechanic_id">Мастер</label>
                            <select class="select" id="mechanic_id" name="mechanic_id">
                                <option value="">Не назначен</option>
                                @foreach ($mechanics as $mechanic)
                                    <option value="{{ $mechanic->id }}" @selected(old('mechanic_id', $order->mechanic_id) == $mechanic->id)>
                                        {{ $mechanic->name }} · {{ number_format((float) $mechanic->hourly_rate, 0, ',', ' ') }} ₽/ч
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="advisor_id">Мастер-приёмщик</label>
                            <select class="select" id="advisor_id" name="advisor_id">
                                @foreach ($advisors as $advisor)
                                    <option value="{{ $advisor->id }}" @selected(old('advisor_id', $order->advisor_id ?? auth()->id()) == $advisor->id)>
                                        {{ $advisor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="intake_at">Дата и время приёмки <span class="req">*</span></label>
                            <input class="input" id="intake_at" type="datetime-local" name="intake_at" required
                                   value="{{ old('intake_at', $order->intake_at ? $order->intake_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="field">
                            <label for="promised_at">Обещанная дата выдачи</label>
                            <input class="input" id="promised_at" type="datetime-local" name="promised_at"
                                   value="{{ old('promised_at', $order->promised_at?->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="field">
                            <label for="discount">Скидка на наряд, %</label>
                            <input class="input" id="discount" type="number" name="discount" min="0" max="100"
                                   value="{{ old('discount', (float) ($order->discount ?? 0)) }}">
                        </div>

                        <div class="field">
                            <label>Документы</label>
                            <label class="check">
                                <input type="checkbox" name="is_invoice" value="1" @checked(old('is_invoice', $order->is_invoice))>
                                Юр. лицо / счёт-фактура
                            </label>
                        </div>

                        <div class="field full">
                            <label for="complaint">Жалобы клиента / что делаем</label>
                            <textarea class="input" id="complaint" name="complaint"
                                      placeholder="Например: стук в передней подвеске на неровностях, горит чек ABS">{{ old('complaint', $order->complaint) }}</textarea>
                        </div>

                        <div class="field full">
                            <label for="note">Внутренние заметки</label>
                            <textarea class="input" id="note" name="note">{{ old('note', $order->note) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        {{ $order->exists ? 'Сохранить наряд' : 'Создать наряд' }}
                    </button>
                    <a class="btn" href="{{ route('orders.index') }}">Отмена</a>
                    @if ($order->exists)
                        <span class="right small muted">Номер {{ $order->number }}</span>
                    @endif
                </div>
            </div>

            <div class="stack">
                <div class="card">
                    <div class="card-head"><h3>Памятка</h3></div>
                    <div class="card-body">
                        <ol class="small muted" style="padding-left:18px;line-height:1.9;margin:0">
                            <li>Выберите клиента и его автомобиль</li>
                            <li>Укажите пробег и жалобы клиента</li>
                            <li>Назначьте пост и мастера</li>
                            <li>Создайте наряд и добавьте работы с запчастями</li>
                            <li>По готовности переведите в статус «Готов к выдаче»</li>
                        </ol>
                    </div>
                </div>

                @if ($order->exists)
                    <div class="card">
                        <div class="card-head"><h3>Текущее состояние</h3></div>
                        <div class="card-body">
                            <dl class="desc" style="margin:0">
                                <dt>Статус</dt>
                                <dd><span class="badge b-{{ \App\Models\Order::statusColor($order->status) }}">{{ \App\Models\Order::statusLabel($order->status) }}</span></dd>
                                <dt>Работ</dt>
                                <dd>{{ $order->works()->count() }}</dd>
                                <dt>Запчастей</dt>
                                <dd>{{ $order->parts()->count() }}</dd>
                                <dt>Итого</dt>
                                <dd>{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</dd>
                                <dt>Оплачено</dt>
                                <dd>{{ number_format((float) $order->paid, 0, ',', ' ') }} ₽</dd>
                            </dl>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const customerSelect = document.querySelector('[data-customer]');
            const vehicleSelect = document.querySelector('[data-vehicle]');
            const mileageInput = document.querySelector('[data-mileage]');
            const vehicles = @json($vehicles->map(fn ($v) => ['id' => $v->id, 'customer_id' => $v->customer_id, 'label' => trim($v->brand.' '.$v->model.($v->plate ? ' · '.$v->plate : ''))]));
            const mileages = @json($vehicles->mapWithKeys(fn ($v) => [$v->id => $v->mileage]));

            function renderVehicles() {
                const customerId = customerSelect.value;
                const current = vehicleSelect.value;
                vehicleSelect.innerHTML = '<option value="">— выберите автомобиль —</option>';
                vehicles.filter(v => !customerId || String(v.customer_id) === String(customerId)).forEach(v => {
                    const opt = new Option(v.label, v.id);
                    if (String(v.id) === String(current)) opt.selected = true;
                    vehicleSelect.appendChild(opt);
                });
            }

            vehicleSelect.addEventListener('change', function () {
                if (mileages[this.value] && (!mileageInput.value || mileageInput.value == '0')) {
                    mileageInput.value = mileages[this.value];
                }
            });

            customerSelect.addEventListener('change', renderVehicles);
            renderVehicles();
        })();
    </script>
@endpush