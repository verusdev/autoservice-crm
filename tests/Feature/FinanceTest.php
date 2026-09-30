<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'admin']);
    }

    private function orderWithDebt(float $total, float $paid): Order
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();

        return Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'issued',
            'closed_at' => now(),
            'total' => $total,
            'paid' => $paid,
        ]);
    }

    public function test_debtor_page_lists_unpaid_orders(): void
    {
        $order = $this->orderWithDebt(10_000, 4000);

        $this->actingAs($this->manager)
            ->get('/finance')
            ->assertOk()
            ->assertSee($order->customer->name)
            ->assertSee($order->number);
    }

    public function test_paid_orders_do_not_appear_in_debtor_list(): void
    {
        $this->orderWithDebt(10_000, 10_000);

        $this->actingAs($this->manager)->get('/finance')->assertOk();
        $this->assertSame(0.0, (float) Order::whereColumn('paid', '<', 'total')->get()->sum('total'));
    }

    public function test_customer_debt_accessor_sums_unpaid_totals(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();

        Order::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'issued',
            'total' => 3000,
            'paid' => 1000,
        ]);

        $this->assertSame(4000.0, $customer->debt());
    }

    public function test_cash_page_filters_by_method(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total' => 2000,
            'paid' => 2000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 2000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->get('/payments?method=cash')
            ->assertOk();

        $this->assertSame(2000.0, (float) Payment::where('method', 'cash')->sum('amount'));
    }

    public function test_deleting_payment_recalculates_order_paid_amount(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total' => 5000,
            'paid' => 0,
            'status' => 'issued',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 5000,
        ]);

        $order->recalculatePaid();
        $this->assertSame(5000.0, (float) $order->fresh()->paid);

        $this->actingAs($this->manager)->delete("/payments/{$payment->id}");

        $this->assertSame(0.0, (float) $order->fresh()->paid);
    }

    public function test_csv_export_returns_a_file(): void
    {
        $this->orderWithDebt(10_000, 10_000);

        $this->actingAs($this->manager)
            ->get('/reports/export?period=month')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
