<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerVehicleTest extends TestCase
{
    use RefreshDatabase;

    private User $advisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->advisor = User::factory()->create(['role' => 'advisor']);
    }

    public function test_customer_can_be_created(): void
    {
        $this->actingAs($this->advisor)->post('/customers', [
            'name' => 'Пётр Иванов',
            'phone' => '+7 999 123-45-67',
            'type' => 'person',
            'discount' => 5,
        ]);

        $customer = Customer::where('name', 'Пётр Иванов')->firstOrFail();

        $this->assertSame('79991234567', $customer->phone, 'Телефон нормализуется в цифры');
        $this->assertSame(5, $customer->discount);
    }

    public function test_customer_with_orders_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->actingAs($this->advisor)
            ->delete("/customers/{$customer->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_vehicle_is_attached_to_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->advisor)->post('/vehicles', [
            'customer_id' => $customer->id,
            'brand' => 'Toyota',
            'model' => 'Camry',
            'year' => 2019,
            'mileage' => 88_000,
            'plate' => 'А123ВС77',
        ])->assertSessionHasNoErrors();

        $vehicle = Vehicle::where('brand', 'Toyota')->firstOrFail();

        $this->assertSame($customer->id, $vehicle->customer_id);
        $this->assertSame(88_000, $vehicle->mileage);
    }

    public function test_customer_card_shows_turnover_and_debt(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();

        Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'issued',
            'total' => 12_000,
            'paid' => 5_000,
        ]);

        $this->actingAs($this->advisor)
            ->get("/customers/{$customer->id}")
            ->assertOk()
            ->assertSee('12 000')
            ->assertSee('7 000');
    }

    public function test_vehicle_form_is_available_without_customer(): void
    {
        $this->actingAs($this->advisor)->get('/vehicles/create')->assertOk();
    }
}
