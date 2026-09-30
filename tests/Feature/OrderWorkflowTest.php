<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Part;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $advisor;

    private Customer $customer;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->advisor = User::factory()->create(['role' => 'advisor']);
        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->for($this->customer)->create();
    }

    private function storeOrder(array $overrides = []): Order
    {
        $response = $this->actingAs($this->advisor)->post('/orders', array_merge([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'post_id' => Post::factory()->create()->id,
            'mileage' => 123_456,
            'intake_at' => now()->format('Y-m-d\TH:i'),
            'complaint' => 'Стук в подвеске',
            'discount' => 0,
        ], $overrides));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        return Order::latest('id')->first();
    }

    public function test_order_is_created_with_generated_number_and_updates_vehicle_mileage(): void
    {
        $order = $this->storeOrder();

        $this->assertStringStartsWith('ЗН-', $order->number);
        $this->assertSame('new', $order->status);
        $this->assertSame($this->advisor->id, $order->advisor_id);
        $this->assertSame(123_456, $this->vehicle->fresh()->mileage);
    }

    public function test_adding_work_recalculates_order_total(): void
    {
        $order = $this->storeOrder();

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/works", [
                'name' => 'Замена масла двигателя',
                'norm_hours' => 2,
                'hourly_rate' => 1000,
                'price' => 2500,
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertCount(1, $order->works);
        $this->assertSame(2500.0, (float) $order->total_work);
        $this->assertSame(2500.0, (float) $order->total);
    }

    public function test_work_uses_service_price_and_toggles_status(): void
    {
        $order = $this->storeOrder();
        $service = Service::factory()->create(['norm_hours' => 3, 'price' => 4500]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/works", [
                'service_id' => $service->id,
                'norm_hours' => 3,
            ])
            ->assertSessionHasNoErrors();

        $work = $order->works()->firstOrFail();
        $this->assertSame($service->name, $work->name);
        $this->assertSame(4500.0, (float) $work->price);

        $this->actingAs($this->advisor)->patch("/works/{$work->id}/toggle");

        $this->assertTrue($work->fresh()->isDone());
        $this->assertSame('ready', $order->fresh()->status, 'Наряд с выполненными работами должен стать готовым');
    }

    public function test_adding_part_writes_off_stock_and_records_movement(): void
    {
        $order = $this->storeOrder();
        $part = Part::factory()->create(['qty' => 10, 'sell_price' => 900]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 3])
            ->assertSessionHasNoErrors();

        $this->assertSame(7.0, (float) $part->fresh()->qty);
        $this->assertSame(2700.0, (float) $order->fresh()->total_parts);
        $this->assertSame(2700.0, (float) $order->fresh()->total);

        $this->assertDatabaseHas('stock_movements', [
            'part_id' => $part->id,
            'order_id' => $order->id,
            'type' => 'out',
            'qty' => 3,
        ]);
    }

    public function test_cannot_issue_more_parts_than_in_stock(): void
    {
        $order = $this->storeOrder();
        $part = Part::factory()->create(['qty' => 2]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 5])
            ->assertSessionHasErrors('part_id');

        $this->assertSame(2.0, (float) $part->fresh()->qty);
    }

    public function test_removing_order_part_returns_it_to_stock(): void
    {
        $order = $this->storeOrder();
        $part = Part::factory()->create(['qty' => 10]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 4]);

        $orderPart = $order->parts()->firstOrFail();

        $this->actingAs($this->advisor)
            ->delete("/order-parts/{$orderPart->id}")
            ->assertSessionHas('success');

        $this->assertSame(10.0, (float) $part->fresh()->qty);
        $this->assertSame(0.0, (float) $order->fresh()->total);
    }

    public function test_full_payment_issues_the_order(): void
    {
        $order = $this->storeOrder();
        $order->update(['total' => 5000, 'paid' => 0]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/payments", ['amount' => 5000, 'method' => 'card'])
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(5000.0, (float) $order->paid);
        $this->assertSame('issued', $order->status);
        $this->assertNotNull($order->closed_at);
        $this->assertTrue($order->isPaid());
    }

    public function test_partial_payment_keeps_order_open_and_creates_debt(): void
    {
        $order = $this->storeOrder();
        $order->update(['total' => 5000, 'paid' => 0, 'status' => 'ready']);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/payments", ['amount' => 2000, 'method' => 'cash']);

        $order->refresh();

        $this->assertSame('ready', $order->status);
        $this->assertSame(3000.0, $order->balance);
        $this->assertFalse($order->isPaid());
    }

    public function test_status_change_to_issued_sets_closing_date(): void
    {
        $order = $this->storeOrder();

        $this->actingAs($this->advisor)
            ->patch("/orders/{$order->id}/status", ['status' => 'ready']);

        $this->actingAs($this->advisor)
            ->patch("/orders/{$order->id}/status", ['status' => 'issued']);

        $order->refresh();
        $this->assertSame('issued', $order->status);
        $this->assertNotNull($order->closed_at);
    }

    public function test_discount_is_applied_to_order_total(): void
    {
        $order = $this->storeOrder(['discount' => 10]);
        $part = Part::factory()->create(['qty' => 5, 'sell_price' => 1000]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 5]);

        $order->refresh();
        $this->assertSame(4500.0, (float) $order->total, 'Скидка 10% должна применяться к итогу наряда');
    }

    public function test_customer_personal_discount_applies_when_order_discount_is_zero(): void
    {
        $this->customer->update(['discount' => 5]);
        $order = $this->storeOrder();
        $part = Part::factory()->create(['qty' => 4, 'sell_price' => 1000]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 4]);

        $this->assertSame(3800.0, (float) $order->fresh()->total);
    }

    public function test_deleting_order_returns_parts_to_stock(): void
    {
        $order = $this->storeOrder();
        $part = Part::factory()->create(['qty' => 10]);

        $this->actingAs($this->advisor)
            ->post("/orders/{$order->id}/parts", ['part_id' => $part->id, 'qty' => 3]);

        $this->actingAs($this->advisor)
            ->delete("/orders/{$order->id}")
            ->assertSessionHas('success');

        $this->assertSame(10.0, (float) $part->fresh()->qty);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_issued_order_cannot_be_deleted(): void
    {
        $order = $this->storeOrder();
        $order->update(['status' => 'issued', 'closed_at' => now()]);

        $this->actingAs($this->advisor)
            ->delete("/orders/{$order->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
