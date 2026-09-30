<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'admin']);
    }

    public function test_incoming_increases_stock_and_writes_movement(): void
    {
        $part = Part::factory()->create(['qty' => 5]);

        $this->actingAs($this->manager)
            ->post('/stock', ['type' => 'in', 'part_id' => $part->id, 'qty' => 10, 'price' => 400])
            ->assertSessionHas('success');

        $this->assertSame(15.0, (float) $part->fresh()->qty);
        $this->assertDatabaseHas('stock_movements', ['part_id' => $part->id, 'type' => 'in', 'qty' => 10]);
    }

    public function test_outgoing_decreases_stock(): void
    {
        $part = Part::factory()->create(['qty' => 12]);

        $this->actingAs($this->manager)
            ->post('/stock', ['type' => 'out', 'part_id' => $part->id, 'qty' => 4, 'note' => 'Брак']);

        $this->assertSame(8.0, (float) $part->fresh()->qty);
    }

    public function test_cannot_write_off_more_than_in_stock(): void
    {
        $part = Part::factory()->create(['qty' => 3]);

        $this->actingAs($this->manager)
            ->post('/stock', ['type' => 'out', 'part_id' => $part->id, 'qty' => 5])
            ->assertSessionHasErrors('qty');

        $this->assertSame(3.0, (float) $part->fresh()->qty);
    }

    public function test_inventory_sets_the_factual_quantity(): void
    {
        $part = Part::factory()->create(['qty' => 10]);

        $this->actingAs($this->manager)
            ->post('/stock', ['type' => 'adjust', 'part_id' => $part->id, 'qty' => 7]);

        $this->assertSame(7.0, (float) $part->fresh()->qty);

        $movement = StockMovement::where('type', 'adjust')->latest('id')->firstOrFail();
        $this->assertSame(3.0, (float) $movement->qty, 'Инвентаризация фиксирует разницу, а не факт');
    }

    public function test_deleting_movement_restores_stock(): void
    {
        $part = Part::factory()->create(['qty' => 5]);

        $this->actingAs($this->manager)
            ->post('/stock', ['type' => 'in', 'part_id' => $part->id, 'qty' => 10]);

        $movement = StockMovement::where('type', 'in')->latest('id')->firstOrFail();

        $this->actingAs($this->manager)->delete("/stock/{$movement->id}");

        $this->assertSame(5.0, (float) $part->fresh()->qty);
    }

    public function test_low_stock_part_is_flagged(): void
    {
        $part = Part::factory()->lowStock()->create();

        $this->assertTrue($part->isLow());

        $this->actingAs($this->manager)
            ->get('/parts?low=1')
            ->assertOk()
            ->assertSee($part->name);
    }
}
