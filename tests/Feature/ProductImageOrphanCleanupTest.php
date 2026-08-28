<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Product's image-replace model hook (App\Models\Product::booted()) had two bugs:
 * it ran on `updating` (before the row was actually saved, so a failed UPDATE would
 * leave the database pointing at a file already deleted), and it only ever cleaned
 * up `image_path`, never `image_medium_path`/`image_thumb_path` - so every
 * rename-triggered image replace permanently orphaned the 800w/400w webp variants.
 * Fixed by moving to `updated` (after the row is committed) and covering all three
 * columns. These tests reproduce the orphan directly against the storage disk,
 * independent of which image pipeline (Intervention vs plain store()) wrote the files.
 */
class ProductImageOrphanCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacing_all_three_image_sizes_deletes_the_old_files(): void
    {
        Storage::fake('public');

        $product = Product::create(['product_name' => 'Orphan Test Widget']);

        Storage::disk('public')->put('products/1/old-1200.webp', 'x');
        Storage::disk('public')->put('products/1/old-800.webp', 'x');
        Storage::disk('public')->put('products/1/old-400.webp', 'x');

        $product->image_disk = 'public';
        $product->image_path = 'products/1/old-1200.webp';
        $product->image_medium_path = 'products/1/old-800.webp';
        $product->image_thumb_path = 'products/1/old-400.webp';
        $product->save();

        Storage::disk('public')->put('products/1/new-1200.webp', 'y');
        Storage::disk('public')->put('products/1/new-800.webp', 'y');
        Storage::disk('public')->put('products/1/new-400.webp', 'y');

        $product->image_path = 'products/1/new-1200.webp';
        $product->image_medium_path = 'products/1/new-800.webp';
        $product->image_thumb_path = 'products/1/new-400.webp';
        $product->save();

        Storage::disk('public')->assertMissing('products/1/old-1200.webp');
        Storage::disk('public')->assertMissing('products/1/old-800.webp');
        Storage::disk('public')->assertMissing('products/1/old-400.webp');
        Storage::disk('public')->assertExists('products/1/new-1200.webp');
        Storage::disk('public')->assertExists('products/1/new-800.webp');
        Storage::disk('public')->assertExists('products/1/new-400.webp');
    }

    public function test_saving_without_changing_the_image_does_not_delete_it(): void
    {
        Storage::fake('public');

        $product = Product::create(['product_name' => 'Untouched Image Widget']);
        Storage::disk('public')->put('products/2/keep-1200.webp', 'x');
        $product->image_disk = 'public';
        $product->image_path = 'products/2/keep-1200.webp';
        $product->save();

        // An unrelated field update must not disturb the existing image.
        $product->category = 'Some Category';
        $product->save();

        Storage::disk('public')->assertExists('products/2/keep-1200.webp');
    }
}
