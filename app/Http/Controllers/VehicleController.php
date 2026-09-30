<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $vehicles = Vehicle::with('customer')
            ->when($term, function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('plate', 'like', $like)
                    ->orWhere('vin', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)));
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('vehicles.index', compact('vehicles', 'term'));
    }

    public function create(Request $request): View
    {
        return view('vehicles.form', [
            'vehicle' => new Vehicle,
            'customers' => Customer::orderBy('name')->get(),
            'selectedCustomer' => (int) $request->query('customer', 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vehicle = Vehicle::create($this->validated($request));

        return redirect()->route('customers.show', $vehicle->customer_id)
            ->with('success', 'Автомобиль добавлен');
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.form', [
            'vehicle' => $vehicle,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validated($request));

        return redirect()->route('customers.show', $vehicle->customer_id)
            ->with('success', 'Данные автомобиля обновлены');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить автомобиль, у которого есть заказ-наряды');
        }

        $customerId = $vehicle->customer_id;
        $vehicle->delete();

        return redirect()->route('customers.show', $customerId)->with('success', 'Автомобиль удалён');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.((int) date('Y') + 2)],
            'vin' => ['nullable', 'string', 'max:32'],
            'plate' => ['nullable', 'string', 'max:16'],
            'color' => ['nullable', 'string', 'max:40'],
            'engine' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'transmission' => ['nullable', 'string', 'max:32'],
            'fuel' => ['nullable', 'string', 'max:32'],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'next_service_date' => ['nullable', 'date'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], [
            'customer_id' => 'Владелец',
            'brand' => 'Марка',
            'model' => 'Модель',
            'mileage' => 'Пробег',
        ]);

        $data['mileage'] = (int) ($data['mileage'] ?? 0);

        return $data;
    }
}
