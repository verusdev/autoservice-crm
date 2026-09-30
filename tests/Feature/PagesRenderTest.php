<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Part;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_all_main_pages_render(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id]);
        $part = Part::factory()->create();
        Work::factory()->for($order)->create();
        Service::factory()->create();
        Post::factory()->create();

        $pages = [
            '/dashboard',
            '/orders',
            '/orders/create',
            "/orders/{$order->id}",
            "/orders/{$order->id}/edit",
            '/customers',
            "/customers/{$customer->id}",
            '/customers/create',
            '/vehicles',
            '/vehicles/create',
            '/parts',
            '/parts/create',
            "/parts/{$part->id}/edit",
            '/stock',
            '/payments',
            '/finance',
            '/employees',
            '/employees/create',
            '/posts',
            '/posts/create',
            '/reports',
            '/reports/export',
            '/orders?status=ready',
            '/orders?status=issued&q=ЗН',
            '/parts?low=1',
            '/stock?type=in',
            '/reports?period=year',
            '/reports?period=all',
            '/payments?method=cash',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }
}
