<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPart;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Work;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $term = trim((string) $request->query('q', ''));

        $orders = Order::with(['customer', 'vehicle', 'post', 'mechanic'])
            ->search($term)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($request->query('post'), fn ($q) => $q->where('post_id', $request->query('post')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('intake_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('intake_at', '<=', $request->query('to')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = Order::selectRaw('COALESCE(SUM(total),0) as total, COALESCE(SUM(paid),0) as paid, COUNT(*) as cnt')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->first();

        return view('orders.index', [
            'orders' => $orders,
            'status' => $status,
            'term' => $term,
            'summary' => $summary,
            'posts' => Post::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('orders.form', [
            'order' => new Order(['intake_at' => now(), 'mileage' => 0]),
            'customers' => $this->customers(),
            'vehicles' => Vehicle::with('customer')->orderByDesc('id')->get(),
            'posts' => Post::where('is_active', true)->orderBy('name')->get(),
            'mechanics' => $this->mechanics(),
            'advisors' => $this->advisors(),
            'selectedVehicle' => (int) $request->query('vehicle'),
            'selectedCustomer' => (int) $request->query('customer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['number'] = Order::nextNumber();
        $data['advisor_id'] ??= auth()->id();
        $data['status'] = 'new';

        $order = Order::create($data);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Заказ-наряд '.$order->number.' создан. Добавьте работы и запчасти.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'vehicle', 'post', 'mechanic', 'advisor', 'payments.user']);

        $works = $order->works()->with('mechanic')->get();
        $parts = $order->parts()->with('part')->get();

        $workSum = (float) $works->sum('price');
        $partsSum = (float) $parts->sum(fn (OrderPart $p) => $p->qty * $p->price);

        return view('orders.show', [
            'order' => $order,
            'works' => $works,
            'parts' => $parts,
            'workSum' => $workSum,
            'partsSum' => $partsSum,
            'services' => Service::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category'),
            'catalog' => Part::where('is_active', true)->orderBy('name')->get(),
            'mechanics' => $this->mechanics(),
            'payments' => Payment::methods(),
        ]);
    }

    public function edit(Order $order): View
    {
        return view('orders.form', [
            'order' => $order,
            'customers' => $this->customers(),
            'vehicles' => Vehicle::with('customer')->orderByDesc('id')->get(),
            'posts' => Post::where('is_active', true)->orderBy('name')->get(),
            'mechanics' => $this->mechanics(),
            'advisors' => $this->advisors(),
            'selectedVehicle' => $order->vehicle_id,
            'selectedCustomer' => $order->customer_id,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $order->update($this->validated($request));
        $order->load('customer')->recalculate();

        return redirect()->route('orders.show', $order)->with('success', 'Заказ-наряд обновлён');
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->status === 'issued') {
            return back()->with('error', 'Выданный заказ-наряд удалить нельзя — оформите возврат через новый наряд');
        }

        DB::transaction(function () use ($order) {
            $order->parts()->whereNotNull('part_id')->get()->each(function (OrderPart $row) {
                Part::whereKey($row->part_id)->increment('qty', (float) $row->qty);
            });
            $order->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Заказ-наряд удалён, запчасти возвращены на склад');
    }

    /* ---------------- статусы ---------------- */

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Order::statuses()))],
        ]);

        $order->status = $data['status'];

        if ($order->status === 'issued') {
            $order->closed_at = now();
        } elseif (in_array($order->status, ['new', 'in_progress', 'waiting', 'ready'], true)) {
            $order->closed_at = null;
        }

        $order->save();

        return back()->with('success', 'Статус: '.Order::statusLabel($order->status));
    }

    /* ---------------- работы ---------------- */

    public function addWork(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'name' => ['nullable', 'string', 'max:160'],
            'norm_hours' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:255'],
        ], [], [
            'norm_hours' => 'Нормо-часы',
            'name' => 'Наименование работы',
        ]);

        if (empty($data['name']) && ! empty($data['service_id'])) {
            $service = Service::find($data['service_id']);
            $data['name'] = $service?->name ?? 'Работа';
        }
        if (empty($data['name'])) {
            return back()->withErrors(['name' => 'Укажите наименование работы'])->withInput();
        }

        $rate = (float) ($data['hourly_rate'] ?? $order->post?->hourly_rate ?? 900);
        $hours = (float) $data['norm_hours'];

        // Цена для клиента: явная цена в форме → цена услуги из справочника → нормо-часы × ставка.
        $price = match (true) {
            isset($data['price']) && $data['price'] !== '' => (float) $data['price'],
            ! empty($data['service_id']) => (float) Service::find($data['service_id'])?->price,
            default => round($hours * $rate, 2),
        };

        $order->works()->create([
            'service_id' => $data['service_id'] ?? null,
            'name' => $data['name'],
            'norm_hours' => $hours,
            'hourly_rate' => $rate,
            'price' => $price,
            'mechanic_id' => $data['mechanic_id'] ?? $order->mechanic_id,
            'comment' => $data['comment'] ?? null,
            'status' => 'planned',
        ]);

        $order->load('customer')->recalculate();

        return back()->with('success', 'Работа добавлена в наряд');
    }

    public function toggleWork(Work $work): RedirectResponse
    {
        $work->status = $work->isDone() ? 'planned' : 'done';
        $work->completed_at = $work->isDone() ? now() : null;
        $work->save();

        $order = $work->order;
        $order->load('customer')->recalculate();

        if ($order->progress() === 100 && $order->status !== 'ready') {
            $order->update(['status' => 'ready']);
            $message = 'Все работы выполнены — наряд переведён в статус «Готов к выдаче»';
        } else {
            $message = 'Статус работы обновлён';
        }

        return back()->with('success', $message);
    }

    public function destroyWork(Work $work): RedirectResponse
    {
        $order = $work->order;
        $work->delete();
        $order->load('customer')->recalculate();

        return back()->with('success', 'Работа удалена из наряда');
    }

    /* ---------------- запчасти ---------------- */

    public function addPart(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'qty' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ], [], [
            'part_id' => 'Запчасть',
            'qty' => 'Количество',
        ]);

        $part = Part::findOrFail($data['part_id']);
        $qty = (float) $data['qty'];

        if ((float) $part->qty < $qty) {
            return back()->withErrors([
                'part_id' => "На складе только {$part->qty} {$part->unit}. Оформите приход или уменьшите количество.",
            ]);
        }

        DB::transaction(function () use ($order, $part, $qty, $data) {
            $order->parts()->create([
                'part_id' => $part->id,
                'name' => $part->name,
                'sku' => $part->sku,
                'qty' => $qty,
                'price' => isset($data['price']) && $data['price'] !== ''
                    ? (float) $data['price']
                    : (float) $part->sell_price,
                'cost' => (float) $part->price,
            ]);

            Part::whereKey($part->id)->decrement('qty', $qty);
            StockMovement::create([
                'part_id' => $part->id,
                'type' => 'out',
                'qty' => $qty,
                'price' => (float) $part->price,
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'note' => 'Выдано по наряду '.$order->number,
            ]);
        });

        $order->load('customer')->recalculate();

        return back()->with('success', "Запчасть «{$part->name}» добавлена");
    }

    public function destroyPart(OrderPart $orderPart): RedirectResponse
    {
        $order = $orderPart->order;

        DB::transaction(function () use ($orderPart, $order) {
            if ($orderPart->part_id) {
                Part::whereKey($orderPart->part_id)->increment('qty', (float) $orderPart->qty);
                StockMovement::create([
                    'part_id' => $orderPart->part_id,
                    'type' => 'in',
                    'qty' => (float) $orderPart->qty,
                    'price' => (float) $orderPart->cost,
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'note' => 'Возврат из наряда '.$order->number,
                ]);
            }
            $orderPart->delete();
        });

        $order->load('customer')->recalculate();

        return back()->with('success', 'Позиция удалена, запчасть возвращена на склад');
    }

    /* ---------------- оплата ---------------- */

    public function addPayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'method' => ['required', 'in:'.implode(',', array_keys(Payment::methods()))],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'amount' => 'Сумма',
            'method' => 'Способ оплаты',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'user_id' => auth()->id(),
            'amount' => (float) $data['amount'],
            'method' => $data['method'],
            'paid_at' => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
            'note' => $data['note'] ?? null,
        ]);

        $order->recalculatePaid();
        $order->load('payments');

        if ($order->isPaid() && in_array($order->status, ['new', 'in_progress', 'waiting', 'ready'], true)) {
            $order->update(['status' => 'issued', 'closed_at' => now()]);
        }

        return back()->with('success', 'Оплата на '.$data['amount'].' ₽ принята');
    }

    public function destroyPayment(Payment $payment): RedirectResponse
    {
        $order = $payment->order;
        $payment->delete();
        $order->recalculatePaid();

        return back()->with('success', 'Платёж удалён');
    }

    /* ---------------- helpers ---------------- */

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'post_id' => ['nullable', 'exists:posts,id'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
            'advisor_id' => ['nullable', 'exists:users,id'],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'intake_at' => ['required', 'date'],
            'promised_at' => ['nullable', 'date'],
            'complaint' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_invoice' => ['nullable', 'boolean'],
        ], [], [
            'customer_id' => 'Клиент',
            'vehicle_id' => 'Автомобиль',
            'mileage' => 'Пробег',
            'intake_at' => 'Дата приёмки',
            'promised_at' => 'Обещанная дата',
        ]);

        $data['mileage'] = (int) ($data['mileage'] ?? 0);
        $data['discount'] = (float) ($data['discount'] ?? 0);
        $data['is_invoice'] = $request->boolean('is_invoice');

        if ($data['intake_at']) {
            $data['intake_at'] = Carbon::parse($data['intake_at']);
        }
        if (! empty($data['promised_at'])) {
            $data['promised_at'] = Carbon::parse($data['promised_at']);
        }

        // Пробег из наряда — источник правды для карточки автомобиля.
        Vehicle::whereKey($data['vehicle_id'])->update(['mileage' => $data['mileage']]);

        return $data;
    }

    private function customers()
    {
        return Customer::orderBy('name')->get();
    }

    private function mechanics()
    {
        return User::where('role', 'mechanic')->where('is_active', true)->orderBy('name')->get();
    }

    private function advisors()
    {
        return User::whereIn('role', ['advisor', 'admin'])->where('is_active', true)->orderBy('name')->get();
    }
}
