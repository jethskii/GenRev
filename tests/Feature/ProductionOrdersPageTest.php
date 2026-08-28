<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The production "orders" page (the live template behind ProductionController::show()/
 * showOrders()) had no flash-message display at all, unlike every other core page in the app
 * (sales, materials, products, settings) - a failed "add order" submission (validation error,
 * insufficient-stock error, etc.) redirected back with the error flashed to session, but the
 * page never rendered it, so the user saw no feedback whatsoever.
 */
class ProductionOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_page_displays_a_flashed_error_message(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 't@test.com',
            'password' => Hash::make('password'), 'role' => 'masters admin', 'is_active' => true,
        ]);
        $product = Product::create(['product_name' => 'Orders Page Test']);

        $response = $this->actingAs($user)
            ->withSession(['error' => 'Something went wrong saving this order.'])
            ->get(route('production.orders', $product->id));

        $response->assertOk();
        $response->assertSee('Something went wrong saving this order.');
    }

    public function test_orders_page_displays_validation_errors(): void
    {
        $user = User::create([
            'name' => 'Test2', 'email' => 't2@test.com',
            'password' => Hash::make('password'), 'role' => 'masters admin', 'is_active' => true,
        ]);
        $product = Product::create(['product_name' => 'Orders Page Test 2']);

        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors = $errors->put('default', new \Illuminate\Support\MessageBag(['materials' => 'Insufficient stock for Flour.']));

        $response = $this->actingAs($user)
            ->withSession(['errors' => $errors])
            ->get(route('production.orders', $product->id));

        $response->assertOk();
        $response->assertSee('Insufficient stock for Flour.');
    }
}
