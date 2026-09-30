<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /** Дебиторка: кто и сколько должен сервису. */
    public function index(): View
    {
        $debtOrders = Order::with(['customer', 'vehicle'])
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid', '<', 'total')
            ->orderBy('intake_at')
            ->get();

        $byCustomer = $debtOrders->groupBy('customer_id')
            ->map(fn ($orders) => [
                'customer' => $orders->first()->customer,
                'count' => $orders->count(),
                'debt' => round($orders->sum(fn (Order $o) => $o->balance), 2),
                'oldest' => $orders->min('intake_at'),
            ])
            ->sortByDesc('debt')
            ->values();

        $overdue = $debtOrders->filter(
            fn (Order $o) => $o->promised_at && $o->promised_at->lt(Carbon::now())
        );

        $monthStart = Carbon::now()->startOfMonth();
        $factMonth = (float) Payment::where('paid_at', '>=', $monthStart)->sum('amount');
        $planMonth = (float) Order::where('status', 'issued')
            ->where('closed_at', '>=', $monthStart)->sum('total');

        $annual = Payment::selectRaw("strftime('%Y-%m', paid_at) as m, SUM(amount) as s")
            ->where('paid_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
            ->groupBy('m')->pluck('s', 'm');

        $chart = collect(range(11, 0))->map(function (int $offset) use ($annual) {
            $date = Carbon::now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $date->format('m.y'),
                'value' => (float) ($annual[$date->format('Y-m')] ?? 0),
            ];
        });

        return view('finance.index', [
            'debtOrders' => $debtOrders,
            'byCustomer' => $byCustomer,
            'overdue' => $overdue,
            'factMonth' => $factMonth,
            'planMonth' => $planMonth,
            'debtTotal' => round($byCustomer->sum('debt'), 2),
            'chart' => $chart,
        ]);
    }
}
