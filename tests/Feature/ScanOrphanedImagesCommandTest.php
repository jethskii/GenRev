<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * New storage-consistency maintenance command (Phase 3): detects image files on disk
 * with no database row referencing them (orphans) and database rows referencing a
 * file that no longer exists on disk (broken references), and can optionally delete
 * orphans on request. Never touches a path a database row (including soft-deleted
 * rows) still references.
 */
class ScanOrphanedImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_an_orphaned_file_and_a_broken_reference(): void
    {
        Storage::fake('public');

        // Orphaned: exists on disk, no row points to it.
        Storage::disk('public')->put('products/999/orphan-1200.webp', 'x');

        // Broken reference: row points to a file that was never written.
        Product::create([
            'product_name' => 'Broken Ref Widget',
            'image_disk' => 'public',
            'image_path' => 'products/1/missing-1200.webp',
        ]);

        $this->artisan('storage:scan-orphans')
            ->expectsOutputToContain('products/999/orphan-1200.webp')
            ->expectsOutputToContain('products/1/missing-1200.webp')
            ->assertExitCode(0);
    }

    public function test_a_soft_deleted_products_image_is_not_reported_as_orphaned(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('products/5/kept-1200.webp', 'x');

        $product = Product::create([
            'product_name' => 'Soft Deleted Widget',
            'image_disk' => 'public',
            'image_path' => 'products/5/kept-1200.webp',
        ]);
        $product->delete();

        $this->artisan('storage:scan-orphans')
            ->expectsOutputToContain('No orphaned files found.')
            ->assertExitCode(0);
    }

    public function test_delete_option_removes_only_orphaned_files(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('products/999/orphan.webp', 'x');
        Product::create([
            'product_name' => 'Kept File Widget',
            'image_disk' => 'public',
            'image_path' => 'products/2/kept.webp',
        ]);
        Storage::disk('public')->put('products/2/kept.webp', 'y');

        $this->artisan('storage:scan-orphans', ['--delete' => true])
            ->expectsConfirmation('Delete 1 orphaned file(s) from disk [public]?', 'yes')
            ->assertExitCode(0);

        Storage::disk('public')->assertMissing('products/999/orphan.webp');
        Storage::disk('public')->assertExists('products/2/kept.webp');
    }
}
