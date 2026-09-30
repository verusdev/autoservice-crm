<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($term, function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('phone2', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('company', 'like', $like));
            })
            ->withCount(['vehicles', 'orders'])
            ->selectSub(
                Order::query()->selectRaw('COALESCE(SUM(total),0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.status', '!=', 'cancelled'),
                'turnover'
            )
            ->selectSub(
                Order::query()->selectRaw('COALESCE(SUM(total - paid),0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.status', '!=', 'cancelled')
                    ->whereColumn('orders.paid', '<', 'orders.total'),
                'debt'
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers', 'term'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Клиент добавлен');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['vehicles' => fn ($q) => $q->orderByDesc('id')]);

        $orders = $customer->orders()
            ->with(['vehicle', 'post', 'mechanic'])
            ->latest('intake_at')
            ->limit(15)
            ->get();

        $stats = [
            'orders' => $customer->orders()->where('status', '!=', 'cancelled')->count(),
            'total' => (float) $customer->orders()->where('status', '!=', 'cancelled')->sum('total'),
            'paid' => (float) $customer->orders()->where('status', '!=', 'cancelled')->sum('paid'),
            'vehicles' => $customer->vehicles()->count(),
        ];
        $stats['debt'] = round($stats['total'] - $stats['paid'], 2);

        return view('customers.show', compact('customer', 'orders', 'stats'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Данные клиента обновлены');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить клиента, у которого есть заказ-наряды');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Клиент удалён');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'phone2' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'type' => ['required', 'in:person,company'],
            'company' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:180'],
            'birth_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'name' => 'ФИО',
            'phone' => 'Телефон',
            'email' => 'E-mail',
            'discount' => 'Скидка',
        ]);

        $data['discount'] = (int) ($data['discount'] ?? 0);
        $data['phone'] = preg_replace('/\D/', '', $data['phone']) ?: $data['phone'];
        if (! empty($data['phone2'])) {
            $data['phone2'] = preg_replace('/\D/', '', $data['phone2']);
        }

        return $data;
    }
}
