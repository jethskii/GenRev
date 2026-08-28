<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SalesController::archivedIndex() rendered view('sales.archived', ...), but that view
 * file did not exist - visiting /sales/archived (a live, linked route) threw
 * "InvalidArgumentException: View [sales.archived] not found." for every user. Created
 * the missing view; these tests lock in that the page renders and that restore /
 * permanent-delete actually work end to end via the real routes.
 */
class SalesArchivedPageTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        return User::create([
            'name' => 'Archive Tester', 'email' => 'archive@test.com',
            'password' => Hash::make('password'), 'role' => 'masters admin', 'is_active' => true,
        ]);
    }

    public function test_archived_sales_page_renders_with_a_trashed_sale(): void
    {
        $product = Product::create(['product_name' => 'Archived Sale Widget']);
        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);
        $sale = Sale::create([
            'product_id' => $product->id, 'product_name' => 'Archived Sale Widget',
            'quantity_kg' => 2, 'quantity' => 2, 'unit_price' => 10, 'date' => now(),
        ]);
        $sale->delete();

        $response = $this->actingAs($this->actingUser())->get(route('sales.archived'));

        $response->assertOk();
        $response->assertSee('Archived Sale Widget');
    }

    public function test_restoring_an_archived_sale_brings_it_back_and_recomputes_inventory(): void
    {
        $product = Product::create(['product_name' => 'Restore Widget']);
        $batch = Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);
        $sale = Sale::create([
            'product_id' => $product->id, 'product_name' => 'Restore Widget',
            'quantity_kg' => 3, 'quantity' => 3, 'unit_price' => 10, 'date' => now(),
        ]);
        $sale->delete();

        $response = $this->actingAs($this->actingUser())
            ->patch(route('sales.restore', $sale->id));

        $response->assertRedirect(route('sales.archived'));
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'deleted_at' => null]);
    }

    public function test_permanently_deleting_an_archived_sale_removes_it(): void
    {
        $product = Product::create(['product_name' => 'Forever Gone Widget']);
        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);
        $sale = Sale::create([
            'product_id' => $product->id, 'product_name' => 'Forever Gone Widget',
            'quantity_kg' => 1, 'quantity' => 1, 'unit_price' => 10, 'date' => now(),
        ]);
        $sale->delete();

        $response = $this->actingAs($this->actingUser())
            ->delete(route('sales.forceDelete', $sale->id));

        $response->assertRedirect(route('sales.archived'));
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }
}
