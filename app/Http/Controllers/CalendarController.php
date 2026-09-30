<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Post;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private const VIEWS = ['day', 'week', 'month'];

    public function index(Request $request): View
    {
        $view = (string) $request->query('view', 'week');
        if (! in_array($view, self::VIEWS, true)) {
            $view = 'week';
        }

        $anchor = Carbon::parse($request->query('date') ?: today())->startOfDay();
        [$from, $to] = $this->period($view, $anchor);

        $posts = Post::where('is_active', true)->orderBy('id')->get();
        $orders = $this->ordersInPeriod($from, $to);

        /** @var array<string, array<int|string, Collection>> $grid */
        $grid = [];
        $load = [];

        foreach ($orders as $order) {
            $postKey = $order->post_id ?? 0;
            $pending = (float) ($order->pending_hours ?: 0);
            // Копируем $from: max() возвращает существующий экземпляр, а addDay() его мутирует.
            $cursor = max($order->intake_at->copy()->startOfDay(), $from->copy());

            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m-d');
                $grid[$key][$postKey] ??= collect();
                $grid[$key][$postKey]->push($order);
                $load[$key][$postKey] = ($load[$key][$postKey] ?? 0) + $pending;
                $cursor->addDay();
            }
        }

        $days = $this->days($view, $from, $to);

        $daily = $days->mapWithKeys(function (Carbon $day) use ($grid, $load, $posts) {
            $key = $day->format('Y-m-d');

            $onPost = collect();
            $totalLoad = 0.0;

            foreach ($posts as $post) {
                $postKey = $post->id;
                $orders = $grid[$key][$postKey] ?? collect();
                $hours = round((float) ($load[$key][$postKey] ?? 0), 1);
                $capacity = max(1, (int) $post->capacity_hours);
                $totalLoad += $hours;

                if ($orders->isNotEmpty()) {
                    $onPost->push([
                        'post' => $post,
                        'orders' => $orders,
                        'hours' => $hours,
                        'percent' => min(150, (int) round($hours / $capacity * 100)),
                    ]);
                }
            }

            $unposted = $grid[$key][0] ?? collect();
            if ($unposted->isNotEmpty()) {
                $onPost->push([
                    'post' => null,
                    'orders' => $unposted,
                    'hours' => round((float) ($load[$key][0] ?? 0), 1),
                    'percent' => 0,
                ]);
            }

            $issuedCount = Order::where('status', 'issued')
                ->whereDate('closed_at', $day)
                ->count();

            $intakeCount = Order::whereDate('intake_at', $day)
                ->where('status', '!=', 'cancelled')
                ->count();

            $totalCapacity = max(1, $posts->sum('capacity_hours'));

            return [$key => [
                'date' => $day,
                'posts' => $onPost,
                'orders' => $orders ?? collect(),
                'ordersCount' => collect($grid[$key] ?? [])->flatten()->unique('id')->count(),
                'issuedCount' => $issuedCount,
                'intakeCount' => $intakeCount,
                'load' => round($totalLoad, 1),
                'loadPercent' => min(150, (int) round($totalLoad / $totalCapacity * 100)),
                'serviceTotal' => (int) Order::where('status', 'issued')
                    ->whereDate('closed_at', $day)->sum('total'),
            ]];
        });

        $overdue = Order::with(['customer', 'vehicle', 'post'])
            ->whereIn('status', Order::openStatuses())
            ->whereNotNull('promised_at')
            ->where('promised_at', '<', now())
            ->orderBy('promised_at')
            ->limit(20)
            ->get();

        $upcoming = Order::with(['customer', 'vehicle', 'post'])
            ->whereIn('status', Order::openStatuses())
            ->whereNotNull('promised_at')
            ->whereBetween('promised_at', [now(), $to])
            ->orderBy('promised_at')
            ->limit(20)
            ->get();

        $serviceDue = Vehicle::with('customer')
            ->whereNotNull('next_service_date')
            ->whereBetween('next_service_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('next_service_date')
            ->limit(30)
            ->get();

        $totals = [
            'orders' => $orders->unique('id')->count(),
            'hours' => round((float) $orders->sum(fn (Order $order) => (float) ($order->pending_hours ?: 0)), 1),
            'issued' => $daily->sum('issuedCount'),
            'revenue' => (int) $daily->sum('serviceTotal'),
            'loadPercent' => $daily->max('loadPercent'),
        ];

        return view('calendar.index', [
            'view' => $view,
            'anchor' => $anchor,
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'daily' => $daily,
            'posts' => $posts,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'serviceDue' => $serviceDue,
            'totals' => $totals,
            'title' => $this->title($view, $anchor, $from, $to),
            'prev' => $this->previous($view, $anchor)->format('Y-m-d'),
            'next' => $this->next($view, $anchor)->format('Y-m-d'),
            'today' => today()->format('Y-m-d'),
        ]);
    }

    private function title(string $view, Carbon $anchor, Carbon $from, Carbon $to): string
    {
        return match ($view) {
            'day' => $anchor->translatedFormat('d F Y (l)'),
            'month' => $anchor->translatedFormat('F Y'),
            default => $from->format('d.m').' — '.$to->format('d.m.Y'),
        };
    }

    /**
     * Наряды, которые находятся в сервисе хотя бы один день запрошенного периода.
     *
     * @return Collection<int, Order>
     */
    private function ordersInPeriod(Carbon $from, Carbon $to): Collection
    {
        return Order::with(['customer', 'vehicle', 'post', 'mechanic'])
            ->withSum('works', 'norm_hours')
            ->withSum(['works as pending_hours' => fn ($query) => $query->where('status', 'planned')], 'norm_hours')
            ->where('status', '!=', 'cancelled')
            ->where('intake_at', '<=', $to->copy()->endOfDay())
            ->where(fn ($query) => $query->whereNull('closed_at')->orWhere('closed_at', '>=', $from))
            ->orderByRaw("CASE status WHEN 'ready' THEN 0 WHEN 'waiting' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('promised_at')
            ->get();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(string $view, Carbon $anchor): array
    {
        return match ($view) {
            'day' => [$anchor, $anchor->copy()->endOfDay()],
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            default => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
        };
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function days(string $view, Carbon $from, Carbon $to): Collection
    {
        if ($view === 'month') {
            // Неделя начинается с понедельника: показываем полные 6 строк.
            $start = $from->copy()->subDays((int) $from->dayOfWeekIso - 1);
            $last = $to->copy()->addDays(7 - (int) $to->dayOfWeekIso);

            return $this->range($start, $last);
        }

        return $this->range($from, $to->copy()->startOfDay());
    }

    /**
     * Список дней периода включительно.
     *
     * @return Collection<int, Carbon>
     */
    private function range(Carbon $from, Carbon $to): Collection
    {
        $days = collect();
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    private function previous(string $view, Carbon $anchor): Carbon
    {
        return match ($view) {
            'day' => $anchor->copy()->subDay(),
            'month' => $anchor->copy()->subMonthNoOverflow(),
            default => $anchor->copy()->subWeek(),
        };
    }

    private function next(string $view, Carbon $anchor): Carbon
    {
        return match ($view) {
            'day' => $anchor->copy()->addDay(),
            'month' => $anchor->copy()->addMonthNoOverflow(),
            default => $anchor->copy()->addWeek(),
        };
    }
}
