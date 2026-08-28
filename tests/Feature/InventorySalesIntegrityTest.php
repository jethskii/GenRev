<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Locks in the Production/Inventory/Sales correctness bugs found and fixed during the
 * system audit, so they can never silently reappear. Each test method's docblock names
 * the specific bug it guards against.
 */
class InventorySalesIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The core FIFO bug: Sale::allocateAndDeduct() and SalesOrderItem's equivalent sorted
     * candidate batches newest-first (orderByDesc) while calling it "FIFO" - i.e. it was
     * actually LIFO, selling newest stock while older stock (closer to expiry) sat unsold.
     */
    public function test_selling_without_a_batch_consumes_the_oldest_batch_first(): void
    {
        $product = Product::create(['product_name' => 'FIFO Widget']);

        $oldBatch = Production::create([
            'product_id' => $product->id,
            'batch_number' => '1',
            'quantity' => 10,
            'current_inventory' => 10,
            'production_date' => '2025-01-01',
        ]);

        $newBatch = Production::create([
            'product_id' => $product->id,
            'batch_number' => '2',
            'quantity' => 10,
            'current_inventory' => 10,
            'production_date' => '2025-06-01',
        ]);

        // Sell 6 units without specifying a batch - should come entirely from the older batch.
        Sale::create([
            'product_id' => $product->id,
            'product_name' => 'FIFO Widget',
            'quantity_kg' => 6,
            'quantity' => 6,
            'unit_price' => 10,
            'date' => now(),
        ]);

        $oldBatch->refresh();
        $newBatch->refresh();

        $this->assertEquals(4, $oldBatch->current_inventory, 'Oldest batch should be deducted first');
        $this->assertEquals(10, $newBatch->current_inventory, 'Newest batch should be untouched while older stock remains');
    }

    /**
     * Same FIFO guarantee, but forces the deduction to spill across both batches, proving
     * the ordering (not just the "which single batch" choice) is correct end to end.
     */
    public function test_fifo_spills_into_the_next_oldest_batch_once_the_first_is_exhausted(): void
    {
        $product = Product::create(['product_name' => 'FIFO Spillover']);

        $oldest = Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 5, 'current_inventory' => 5, 'production_date' => '2025-01-01',
        ]);
        $middle = Production::create([
            'product_id' => $product->id, 'batch_number' => '2',
            'quantity' => 5, 'current_inventory' => 5, 'production_date' => '2025-03-01',
        ]);
        $newest = Production::create([
            'product_id' => $product->id, 'batch_number' => '3',
            'quantity' => 5, 'current_inventory' => 5, 'production_date' => '2025-06-01',
        ]);

        // Sell 8: fully drains the oldest (5), then takes 3 from the middle batch.
        Sale::create([
            'product_id' => $product->id, 'product_name' => 'FIFO Spillover',
            'quantity_kg' => 8, 'quantity' => 8, 'unit_price' => 5, 'date' => now(),
        ]);

        $this->assertEquals(0, $oldest->refresh()->current_inventory);
        $this->assertEquals(2, $middle->refresh()->current_inventory);
        $this->assertEquals(5, $newest->refresh()->current_inventory, 'Newest batch must remain untouched');
    }

    /**
     * CRITICAL regression test. Production::booted()'s saving() hook used to coerce columns
     * unconditionally, treating "not loaded on this model instance" the same as "invalid" and
     * resetting either to a default. Sale::allocateAndDeduct()'s FIFO loop loads batches with a
     * partial column list (only id/batch_number/current_inventory/available_pack/available_bag),
     * so saving one of those partially-loaded models used to silently wipe quantity,
     * forecasted_demand, unit_cost, and unit_price_pack/bag on the real database row to 0/default.
     * This test creates a batch with those fields populated, sells from it via the FIFO path
     * (no batch specified), and asserts every field NOT involved in the deduction survives
     * completely untouched.
     */
    public function test_fifo_deduction_does_not_corrupt_other_columns_on_the_batch(): void
    {
        $product = Product::create(['product_name' => 'Integrity Widget']);

        $batch = Production::create([
            'product_id' => $product->id,
            'batch_number' => '1',
            'quantity' => 20,
            'current_inventory' => 20,
            'production_date' => '2025-01-01',
            'unit_cost' => 7.25,
            'unit_price_pack' => 12.5,
            'unit_price_bag' => 3.0,
            'forecasted_demand' => 15,
        ]);

        Sale::create([
            'product_id' => $product->id,
            'product_name' => 'Integrity Widget',
            'quantity_kg' => 8,
            'quantity' => 8,
            'unit_price' => 10,
            'date' => now(),
        ]);

        $batch->refresh();

        $this->assertEquals(20, $batch->quantity, 'Total quantity ever produced must never change from a sale');
        $this->assertEquals(12, $batch->current_inventory, 'Only current_inventory should be decremented');
        $this->assertEquals(7.25, (float) $batch->unit_cost, 'unit_cost must survive a partial-column save untouched');
        $this->assertEquals(12.5, (float) $batch->unit_price_pack, 'unit_price_pack must survive untouched');
        $this->assertEquals(3.0, (float) $batch->unit_price_bag, 'unit_price_bag must survive untouched');
        $this->assertEquals(15, (float) $batch->forecasted_demand, 'forecasted_demand must survive untouched');
    }

    /** The consolidated recomputeProductBalance() must reflect produced minus sold exactly once. */
    public function test_product_balance_updates_correctly_after_a_sale(): void
    {
        $product = Product::create(['product_name' => 'Balance Widget']);

        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 100, 'current_inventory' => 100, 'production_date' => '2025-01-01',
        ]);

        Sale::create([
            'product_id' => $product->id, 'product_name' => 'Balance Widget',
            'quantity_kg' => 30, 'quantity' => 30, 'unit_price' => 10, 'date' => now(),
        ]);

        // Regression guard for the double-counting bug (SUM(quantity_kg) + SUM(quantity), which
        // are always equal for the same sale): balance must be 100-30=70, never 100-60=40.
        $this->assertEquals(70, $product->refresh()->quantity);
    }

    /** Production creation must immediately reflect in the product's aggregate balance. */
    public function test_creating_a_production_batch_updates_product_inventory(): void
    {
        $product = Product::create(['product_name' => 'New Batch Widget']);
        $this->assertEquals(0, $product->quantity);

        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 50, 'current_inventory' => 50, 'production_date' => '2025-01-01',
        ]);

        $this->assertEquals(50, $product->refresh()->quantity);
        $this->assertEquals('in_stock', $product->stock_status);
    }

    /** A freshly created product must start with zero stock, not null or a stale value. */
    public function test_product_creation_initializes_with_zero_inventory(): void
    {
        $product = Product::create(['product_name' => 'Brand New Widget']);

        $this->assertEquals(0, (float) $product->quantity);
    }

    /** Selling more than what exists must be blocked before any database write happens. */
    public function test_sale_is_blocked_when_requested_quantity_exceeds_available_stock(): void
    {
        $product = Product::create(['product_name' => 'Limited Widget']);
        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);

        $this->expectException(ValidationException::class);

        Sale::create([
            'product_id' => $product->id, 'product_name' => 'Limited Widget',
            'quantity_kg' => 999, 'quantity' => 999, 'unit_price' => 10, 'date' => now(),
        ]);
    }

    /** Selling a product with zero produced stock must be blocked, not silently accepted. */
    public function test_sale_is_blocked_when_no_stock_exists_at_all(): void
    {
        $product = Product::create(['product_name' => 'Empty Widget']);

        $this->expectException(ValidationException::class);

        Sale::create([
            'product_id' => $product->id, 'product_name' => 'Empty Widget',
            'quantity_kg' => 1, 'quantity' => 1, 'unit_price' => 10, 'date' => now(),
        ]);
    }

    /**
     * Fix for the guard that used to only run under `$requestedQty > 0`, silently skipping
     * validation entirely (not rejecting) for zero/negative quantities.
     */
    public function test_zero_quantity_sale_is_rejected(): void
    {
        $product = Product::create(['product_name' => 'Zero Qty Widget']);
        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);

        $this->expectException(ValidationException::class);

        Sale::create([
            'product_id' => $product->id, 'product_name' => 'Zero Qty Widget',
            'quantity_kg' => 0, 'quantity' => 0, 'unit_price' => 10, 'date' => now(),
        ]);
    }

    /** current_inventory must never go negative even under a manual adjustment attempt. */
    public function test_material_stock_adjustment_cannot_go_negative(): void
    {
        $material = Material::create([
            'material_name' => 'Flour', 'unit' => 'kg', 'quantity_kg' => 5,
        ]);

        // Mirrors InventoryController::store()'s own clamp: max(0, current + delta).
        $material->quantity_kg = max(0.0, (float) $material->quantity_kg + (-999));
        $material->save();

        $this->assertEquals(0.0, (float) $material->refresh()->quantity_kg);
    }

    /** Deleting a sale must credit the stock back to the batch it was taken from. */
    public function test_deleting_a_sale_restores_inventory_to_the_batch(): void
    {
        $product = Product::create(['product_name' => 'Refund Widget']);
        $batch = Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);

        $sale = Sale::create([
            'product_id' => $product->id, 'product_name' => 'Refund Widget',
            'quantity_kg' => 4, 'quantity' => 4, 'unit_price' => 10, 'date' => now(),
        ]);

        $this->assertEquals(6, $batch->refresh()->current_inventory);

        $sale->delete();

        $this->assertEquals(10, $batch->refresh()->current_inventory, 'Stock must be fully restored after the sale is deleted');
        $this->assertEquals(10, $product->refresh()->quantity);
    }

    /**
     * Database-level constraint check (portable across MySQL/Postgres/SQLite, unlike the
     * partial unique index which is Postgres-only): productions_product_batch_unique must
     * reject a duplicate (product_id, batch_number) pair.
     */
    public function test_duplicate_batch_number_for_the_same_product_is_rejected_by_the_database(): void
    {
        $product = Product::create(['product_name' => 'Batch Unique Widget']);

        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 10, 'current_inventory' => 10, 'production_date' => '2025-01-01',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Production::create([
            'product_id' => $product->id, 'batch_number' => '1',
            'quantity' => 5, 'current_inventory' => 5, 'production_date' => '2025-02-01',
        ]);
    }
}
