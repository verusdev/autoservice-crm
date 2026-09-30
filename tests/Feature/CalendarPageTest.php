<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarPageTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'admin']);
        $this->post = Post::factory()->create(['capacity_hours' => 8]);
    }

    private function order(string $status = 'in_progress', ?string $intake = null, ?string $promised = null): Order
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();

        return Order::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'post_id' => $this->post->id,
            'status' => $status,
            'intake_at' => $intake ?: now()->subDay(),
            'promised_at' => $promised ?: now()->addDay(),
            'closed_at' => $status === 'issued' ? now() : null,
        ]);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/calendar')->assertRedirect('/login');
    }

    public function test_all_three_modes_render(): void
    {
        $this->order();

        foreach (['day', 'week', 'month', 'unknown-mode'] as $view) {
            $this->actingAs($this->manager)
                ->get("/calendar?view={$view}")
                ->assertOk()
                ->assertSee('Нарядов в сервисе', false)
                ->assertSee('Просроченные сроки', false);
        }
    }

    public function test_navigation_arrows_change_period(): void
    {
        $this->actingAs($this->manager)
            ->get('/calendar?view=week&date=2026-03-15')
            ->assertOk()
            ->assertSee('date=2026-03-08', false)
            ->assertSee('date=2026-03-22', false)
            ->assertSee('09.03 — 15.03.2026', false);
    }

    public function test_open_order_is_placed_on_the_calendar(): void
    {
        $order = $this->order('in_progress');

        $this->actingAs($this->manager)
            ->get('/calendar?view=day&date='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertSee($order->vehicle->brand.' '.$order->vehicle->model)
            ->assertSee($order->number);
    }

    public function test_overdue_order_is_marked_late(): void
    {
        $order = $this->order('in_progress', now()->subDays(5), now()->subDay());

        $this->actingAs($this->manager)
            ->get('/calendar?view=week')
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Просрочено сроков');
    }

    public function test_pending_hours_drive_post_load(): void
    {
        $order = $this->order('in_progress');

        Work::factory()->count(2)->for($order)->create(['status' => 'planned', 'norm_hours' => 4]);

        $this->actingAs($this->manager)
            ->get('/calendar?view=day&date='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertSee('8 ч', false); // 2 работы × 4 ч
    }

    public function test_closed_order_in_the_past_is_not_shown_for_today(): void
    {
        $order = $this->order('issued', now()->subDays(10), now()->subDays(9));
        $order->forceFill(['closed_at' => now()->subDays(9)])->save();

        $this->actingAs($this->manager)
            ->get('/calendar?view=day&date='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertDontSee($order->number);
    }

    public function test_cancelled_orders_are_hidden(): void
    {
        $order = $this->order('cancelled');

        $this->actingAs($this->manager)
            ->get('/calendar?view=day&date='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertDontSee($order->number);
    }
}
