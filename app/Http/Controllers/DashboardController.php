<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Vehicle;
use App\Models\Work;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Hex-цвета для кольцевой диаграммы статусов. */
    private const STATUS_COLORS = [
        'new' => '#2563eb',
        'in_progress' => '#d97706',
        'waiting' => '#7c3aed',
        'ready' => '#16a34a',
        'issued' => '#64748b',
        'cancelled' => '#dc2626',
    ];

    public function index(): View
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $open = Order::openStatuses();

        $issuedToday = Order::where('status', 'issued')
            ->whereDate('closed_at', $today)
            ->selectRaw('COALESCE(SUM(total),0) as revenue, COUNT(*) as cnt')
            ->first();

        $cashToday = Payment::whereDate('paid_at', $today)->sum('amount');

        $revenueMonth = Order::where('status', 'issued')
            ->where('closed_at', '>=', $monthStart)
            ->sum('total');

        $openOrders = Order::whereIn('status', $open)->count();

        $wipSum = Order::whereIn('status', $open)
            ->selectRaw('COALESCE(SUM(total - paid),0) as s')->value('s');

        $debts = Order::where('status', '!=', 'cancelled')
            ->whereColumn('paid', '<', 'total')
            ->selectRaw('COALESCE(SUM(total - paid),0) as s')->value('s');

        $avgCheck = (float) (Order::where('status', 'issued')
            ->where('closed_at', '>=', $monthStart)->avg('total') ?? 0);

        $inWork = Order::whereIn('status', ['in_progress', 'waiting'])->count();
        $ready = Order::where('status', 'ready')->count();

        /* --- Выручка по дням за 30 дней --- */
        $revenueByDay = Order::where('status', 'issued')
            ->where('closed_at', '>=', $today->copy()->subDays(29))
            ->selectRaw('DATE(closed_at) as d, SUM(total) as s')
            ->groupBy('d')->pluck('s', 'd');

        $days = collect(range(0, 29))->map(function (int $offset) use ($revenueByDay, $today) {
            $date = $today->copy()->subDays(29 - $offset);

            return [
                'label' => ($offset % 5 === 0 || $offset === 29) ? $date->format('d.m') : '',
                'value' => (float) ($revenueByDay[$date->format('Y-m-d')] ?? 0),
            ];
        });

        /* --- Выручка по месяцам за год --- */
        $revenueByMonth = Order::where('status', 'issued')
            ->where('closed_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
            ->selectRaw("strftime('%Y-%m', closed_at) as m, SUM(total) as s")
            ->groupBy('m')->pluck('s', 'm');

        $months = collect(range(11, 0))->map(function (int $offset) use ($revenueByMonth, $today) {
            $date = $today->copy()->startOfMonth()->subMonths($offset);

            return [
                'label' => $date->format('m.y'),
                'value' => (float) ($revenueByMonth[$date->format('Y-m')] ?? 0),
            ];
        });

        /* --- Структура статусов за месяц --- */
        $statusCounts = Order::selectRaw('status, COUNT(*) as cnt')
            ->where('intake_at', '>=', $monthStart)
            ->groupBy('status')->pluck('cnt', 'status');

        $statusSlices = collect(Order::statuses())
            ->map(fn (array $meta, string $key): array => [
                'label' => $meta['label'],
                'value' => (int) ($statusCounts[$key] ?? 0),
                'color' => self::STATUS_COLORS[$key] ?? '#94a3b8',
            ])
            ->reject(fn (array $slice): bool => $slice['value'] === 0)
            ->values();

        /* --- Популярные работы месяца --- */
        $topWorks = Work::query()
            ->join('orders', 'orders.id', '=', 'works.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('works.created_at', '>=', $monthStart)
            ->selectRaw('works.name as name, COUNT(*) as cnt, COALESCE(SUM(works.price),0) as sum')
            ->groupBy('works.name')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get();

        /* --- Загрузка постов (нормо-часы в работе / мощность за месяц) --- */
        $busyByPost = Work::query()
            ->join('orders', 'orders.id', '=', 'works.order_id')
            ->whereIn('orders.status', ['in_progress', 'waiting', 'ready'])
            ->whereNotNull('orders.post_id')
            ->groupBy('orders.post_id')
            ->selectRaw('orders.post_id as post_id, SUM(works.norm_hours) as hours')
            ->pluck('hours', 'post_id');

        $postsLoad = Post::orderBy('id')->get()->map(function (Post $post) use ($busyByPost): array {
            $busy = round((float) ($busyByPost[$post->id] ?? 0), 1);
            $capacity = max(1, (int) $post->capacity_hours * 22);

            return [
                'name' => $post->name,
                'hours' => $busy,
                'percent' => min(150, (int) round($busy / $capacity * 100)),
            ];
        });

        /* --- Напоминания о ТО --- */
        $serviceDue = Vehicle::with('customer')
            ->whereNotNull('next_service_date')
            ->whereDate('next_service_date', '<=', $today->copy()->addDays(30))
            ->orderBy('next_service_date')
            ->limit(8)
            ->get();

        $lowStock = Part::orderBy('qty')->limit(8)->get()
            ->filter(fn (Part $part): bool => $part->isLow())
            ->values();

        $newCustomers = Customer::orderByDesc('id')->limit(6)->get();

        return view('dashboard', compact(
            'issuedToday', 'cashToday', 'revenueMonth', 'openOrders', 'wipSum', 'debts',
            'avgCheck', 'inWork', 'ready', 'days', 'months', 'statusSlices', 'topWorks',
            'postsLoad', 'serviceDue', 'lowStock', 'newCustomers',
        ));
    }
}
