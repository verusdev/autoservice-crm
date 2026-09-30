@php
    /** Карточка заказ-наряда для календаря. @var \App\Models\Order $order */
    $late = $order->promised_at && $order->promised_at->lt(now()) && $order->isOpen();
@endphp
<a class="cal-card st-{{ $order->status }} {{ $compact ?? false ? 'mini' : '' }}" href="{{ route('orders.show', $order) }}">
    <div class="t">
        <span>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</span>
        @if ($late)
            <span class="late">срок</span>
        @endif
    </div>

    @if (($compact ?? false) === false)
        <div class="m">
            {{ $order->customer?->name }} · {{ $order->number }}
            @if ($order->vehicle?->plate) · {{ $order->vehicle->plate }} @endif
        </div>
        <div class="f">
            <span class="badge b-{{ \App\Models\Order::statusColor($order->status) }}">
                {{ \App\Models\Order::statusLabel($order->status) }}
            </span>
            @if ($order->works_sum)
                <span class="badge b-gray">{{ rtrim(rtrim(number_format((float) $order->works_sum, 1, ',', ' '), '0'), ',') }} ч</span>
            @endif
            @if ($order->promised_at && ! $late)
                <span class="badge b-gray">до {{ $order->promised_at->format('d.m') }}</span>
            @endif
            @if ($order->mechanic)
                <span class="badge b-blue">{{ $order->mechanic->name }}</span>
            @endif
        </div>
    @else
        <div class="m">{{ $order->customer?->name }} · {{ $order->number }}</div>
    @endif
</a>