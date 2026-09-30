<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $from = Carbon::parse(request()->query('from', Carbon::today()->startOfMonth()));
        $to = Carbon::parse(request()->query('to', Carbon::today()));
        $method = (string) request()->query('method', '');

        $payments = Payment::with(['order', 'customer', 'user'])
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($method !== '', fn ($q) => $q->where('method', $method))
            ->orderByDesc('paid_at')
            ->paginate(30)
            ->withQueryString();

        $total = (float) Payment::whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($method !== '', fn ($q) => $q->where('method', $method))
            ->sum('amount');

        $byMethod = Payment::selectRaw('method, COALESCE(SUM(amount),0) as s, COUNT(*) as cnt')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('method')
            ->get()
            ->map(fn ($row) => [
                'label' => Payment::methodLabel($row->method),
                'value' => (float) $row->s,
                'count' => (int) $row->cnt,
            ]);

        $daily = Payment::selectRaw('DATE(paid_at) as d, COALESCE(SUM(amount),0) as s')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('d')->pluck('s', 'd');

        $spanDays = max(1, $from->diffInDays($to));
        $chart = collect(range(0, min(29, $spanDays - 1)))->map(function (int $offset) use ($from, $daily) {
            $date = $from->copy()->addDays($offset);

            return [
                'label' => $date->format('d.m'),
                'value' => (float) ($daily[$date->format('Y-m-d')] ?? 0),
            ];
        });

        $byCashier = Payment::with('user')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COALESCE(SUM(amount),0) as s, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->user?->name ?? '—',
                'value' => (float) $row->s,
                'count' => (int) $row->cnt,
            ])
            ->sortByDesc('value')
            ->values();

        $debtTotal = (float) Order::where('status', '!=', 'cancelled')
            ->whereColumn('paid', '<', 'total')
            ->selectRaw('COALESCE(SUM(total - paid),0) as s')->value('s');

        return view('payments.index', [
            'payments' => $payments,
            'from' => $from,
            'to' => $to,
            'method' => $method,
            'methods' => Payment::methods(),
            'total' => $total,
            'byMethod' => $byMethod,
            'chart' => $chart,
            'byCashier' => $byCashier,
            'debtTotal' => $debtTotal,
        ]);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $order = $payment->order;
        $payment->delete();
        $order->recalculatePaid();

        return back()->with('success', 'Платёж удалён');
    }
}
