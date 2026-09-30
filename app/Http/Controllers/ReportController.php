<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $period = (string) $request->query('period', 'month');
        [$from, $to] = $this->range($period);

        /* --- Выручка по дням --- */
        $issued = Order::where('status', 'issued')->whereBetween('closed_at', [$from, $to]);

        $byDay = (clone $issued)->selectRaw('DATE(closed_at) as d, SUM(total) as s, COUNT(*) as cnt')
            ->groupBy('d')->pluck('s', 'd');
        $countByDay = (clone $issued)->selectRaw('DATE(closed_at) as d, COUNT(*) as cnt')->groupBy('d')->pluck('cnt', 'd');

        $daysTotal = (int) $from->diffInDays($to) + 1;

        // Длинные периоды агрегируем помесячно, чтобы график оставался читаемым.
        if ($daysTotal > 120) {
            $byMonth = (clone $issued)->selectRaw("strftime('%Y-%m', closed_at) as m, SUM(total) as s")
                ->groupBy('m')->pluck('s', 'm');

            $monthsCount = (int) Carbon::parse($from)->startOfMonth()->diffInMonths($to) + 1;
            $chart = collect(range(0, $monthsCount - 1))->map(function (int $offset) use ($from, $byMonth) {
                $date = Carbon::parse($from)->startOfMonth()->addMonths($offset);

                return [
                    'label' => $date->format('m.y'),
                    'value' => (float) ($byMonth[$date->format('Y-m')] ?? 0),
                ];
            });
        } else {
            $chart = collect(range(0, $daysTotal - 1))->map(function (int $offset) use ($from, $byDay) {
                $date = $from->copy()->addDays($offset);

                return [
                    'label' => $date->format('d.m'),
                    'value' => (float) ($byDay[$date->format('Y-m-d')] ?? 0),
                ];
            });
        }

        /* --- Итоги --- */
        $revenue = (float) (clone $issued)->sum('total');
        $ordersCount = (clone $issued)->count();
        $avgCheck = $ordersCount ? $revenue / $ordersCount : 0;

        $paid = (float) Payment::whereBetween('paid_at', [$from, $to])->sum('amount');

        /* --- Маржа --- */
        $workRevenue = (float) Work::whereIn('order_id', (clone $issued)->select('id'))->sum('price');
        $workCost = (float) Work::whereIn('order_id', (clone $issued)->select('id'))
            ->selectRaw('COALESCE(SUM(norm_hours * hourly_rate),0) as s')->value('s');
        $partsRevenue = (float) Order::whereIn('id', (clone $issued)->select('id'))
            ->sum('total_parts');
        $partsCost = (float) DB::table('order_parts')
            ->join('orders', 'orders.id', '=', 'order_parts.order_id')
            ->whereIn('order_parts.order_id', (clone $issued)->select('orders.id'))
            ->selectRaw('COALESCE(SUM(order_parts.qty * order_parts.cost),0) as s')->value('s');

        $margin = $revenue - $workCost - $partsCost;

        /* --- Топ работ --- */
        $topWorks = Work::query()
            ->join('orders', 'orders.id', '=', 'works.order_id')
            ->whereIn('works.order_id', (clone $issued)->select('orders.id'))
            ->selectRaw('works.name as name, COUNT(*) as cnt, SUM(works.norm_hours) as hours, SUM(works.price) as sum')
            ->groupBy('works.name')
            ->orderByDesc('sum')
            ->limit(10)
            ->get();

        /* --- Топ запчастей --- */
        $topParts = DB::table('order_parts')
            ->join('orders', 'orders.id', '=', 'order_parts.order_id')
            ->whereIn('order_parts.order_id', (clone $issued)->select('orders.id'))
            ->selectRaw('order_parts.name as name, SUM(order_parts.qty) as qty, SUM(order_parts.qty * order_parts.price) as sum')
            ->groupBy('order_parts.name')
            ->orderByDesc('sum')
            ->limit(10)
            ->get();

        /* --- Марки автомобилей --- */
        $byBrand = DB::table('orders')
            ->join('vehicles', 'vehicles.id', '=', 'orders.vehicle_id')
            ->where('orders.status', 'issued')
            ->whereBetween('orders.closed_at', [$from, $to])
            ->selectRaw('vehicles.brand as brand, COUNT(*) as cnt, SUM(orders.total) as sum')
            ->groupBy('vehicles.brand')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        /* --- Посты --- */
        $postLoad = Work::query()
            ->join('orders', 'orders.id', '=', 'works.order_id')
            ->leftJoin('posts', 'posts.id', '=', 'orders.post_id')
            ->whereIn('works.order_id', (clone $issued)->select('orders.id'))
            ->whereNotNull('orders.post_id')
            ->groupBy('posts.name')
            ->selectRaw('posts.name as name, SUM(works.norm_hours) as hours, SUM(works.price) as sum')
            ->orderByDesc('hours')
            ->limit(8)
            ->get();

        /* --- Мастера --- */
        $byMechanic = Work::query()
            ->join('orders', 'orders.id', '=', 'works.order_id')
            ->join('users', 'users.id', '=', 'works.mechanic_id')
            ->whereIn('works.order_id', (clone $issued)->select('orders.id'))
            ->groupBy('users.name')
            ->selectRaw('users.name as name, SUM(works.norm_hours) as hours, SUM(works.price) as sum')
            ->orderByDesc('hours')
            ->limit(8)
            ->get();

        /* --- Загрузка постов (текущая) --- */
        $capacityByPost = Post::pluck('capacity_hours', 'id');
        $busyByPost = Order::whereIn('status', Order::openStatuses())
            ->whereNotNull('post_id')
            ->selectRaw('post_id, COUNT(*) as cnt')
            ->groupBy('post_id')
            ->pluck('cnt', 'post_id');

        $postLoadNow = Post::orderBy('name')->get()->map(fn (Post $post) => [
            'name' => $post->name,
            'orders' => (int) ($busyByPost[$post->id] ?? 0),
            'percent' => min(150, (int) round(
                ((int) ($busyByPost[$post->id] ?? 0)) / max(1, (int) $capacityByPost[$post->id] * 22) * 100 * 1
            )),
        ]);

        /* --- Прибыльность по клиентам --- */
        $topCustomers = (clone $issued)
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.name')
            ->selectRaw('customers.name as name, COUNT(*) as cnt, SUM(orders.total) as sum')
            ->orderByDesc('sum')
            ->limit(10)
            ->get();

        $lowStock = Part::whereColumn('qty', '<=', 'min_stock')->orderBy('qty')->limit(10)->get();

        return view('reports.index', compact(
            'period', 'from', 'to', 'chart', 'revenue', 'ordersCount', 'avgCheck', 'paid',
            'workRevenue', 'workCost', 'partsRevenue', 'partsCost', 'margin',
            'topWorks', 'topParts', 'byBrand', 'postLoad', 'byMechanic', 'topCustomers',
            'postLoadNow', 'lowStock', 'countByDay',
        ));
    }

    /** Выгрузка выданных нарядов в CSV. */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range((string) $request->query('period', 'month'));

        $orders = Order::with(['customer', 'vehicle', 'post', 'mechanic'])
            ->where('status', 'issued')
            ->whereBetween('closed_at', [$from, $to])
            ->orderBy('closed_at')
            ->get();

        $filename = 'naryady-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM — чтобы Excel понял кириллицу

            fputcsv($handle, [
                'Номер', 'Дата выдачи', 'Клиент', 'Телефон', 'Автомобиль', 'Госномер',
                'Пробег', 'Пост', 'Мастер', 'Работы', 'Запчасти', 'Скидка %', 'Итого', 'Оплачено', 'Долг',
            ], ';');

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->number,
                    $order->closed_at?->format('d.m.Y H:i'),
                    $order->customer?->name,
                    $order->customer?->phone,
                    trim(($order->vehicle?->brand ?? '').' '.($order->vehicle?->model ?? '')),
                    $order->vehicle?->plate,
                    $order->mileage,
                    $order->post?->name,
                    $order->mechanic?->name,
                    number_format((float) $order->total_work, 2, ',', ''),
                    number_format((float) $order->total_parts, 2, ',', ''),
                    (float) $order->discount,
                    number_format((float) $order->total, 2, ',', ''),
                    number_format((float) $order->paid, 2, ',', ''),
                    number_format($order->balance, 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfDay()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'all' => [Carbon::create(2000, 1, 1), $now->copy()->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };
    }
}
