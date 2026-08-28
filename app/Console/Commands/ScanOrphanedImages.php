<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Production;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Storage consistency check for product/production images: finds files sitting on disk
 * that no database row references (orphans - safe to reclaim) and database rows that
 * reference a file no longer on disk (broken references - a display bug waiting to
 * happen). Read-only by default; --delete removes orphaned files only, never touches
 * the database, and never deletes a path any row (including soft-deleted ones) still
 * references.
 */
class ScanOrphanedImages extends Command
{
    protected $signature = 'storage:scan-orphans
        {--disk=public : Filesystem disk to scan}
        {--delete : Actually delete orphaned files instead of just reporting them}';

    protected $description = 'Report (and optionally clean up) orphaned product/production image files';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $delete = (bool) $this->option('delete');

        $referenced = $this->collectReferencedPaths();
        $onDisk = $this->collectFilesOnDisk($disk);

        $orphaned = array_values(array_diff($onDisk, array_keys($referenced)));
        $broken = array_values(array_filter(
            array_keys($referenced),
            fn (string $path) => ! Storage::disk($disk)->exists($path)
        ));

        $this->info(sprintf(
            'Scanned disk [%s]: %d files on disk, %d referenced by the database.',
            $disk, count($onDisk), count($referenced)
        ));

        if (empty($orphaned)) {
            $this->info('No orphaned files found.');
        } else {
            $this->warn(count($orphaned).' orphaned file(s) found (on disk, not referenced by any row):');
            foreach ($orphaned as $path) {
                $this->line("  - {$path}");
            }
        }

        if (empty($broken)) {
            $this->info('No broken references found.');
        } else {
            $this->error(count($broken).' broken reference(s) found (in the database, missing from disk):');
            foreach ($broken as $path) {
                $this->line("  - {$path} (referenced by: ".$referenced[$path].')');
            }
        }

        if ($delete && ! empty($orphaned)) {
            if (! $this->confirm('Delete '.count($orphaned).' orphaned file(s) from disk ['.$disk.']?', false)) {
                $this->info('Skipped deletion.');

                return self::SUCCESS;
            }

            $deleted = 0;
            foreach ($orphaned as $path) {
                if (Storage::disk($disk)->delete($path)) {
                    $deleted++;
                }
            }
            $this->info("Deleted {$deleted} orphaned file(s).");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> map of referenced path => "Table#id" description
     */
    private function collectReferencedPaths(): array
    {
        $paths = [];

        foreach (Product::withTrashed()->get(['id', 'image_path', 'image_medium_path', 'image_thumb_path']) as $p) {
            foreach ([$p->image_path, $p->image_medium_path, $p->image_thumb_path] as $path) {
                if ($path) {
                    $paths[ltrim($path, '/')] = "products#{$p->id}";
                }
            }
        }

        foreach (Production::withTrashed()->get(['id', 'image_path', 'image_medium_path', 'image_thumb_path']) as $pr) {
            foreach ([$pr->image_path, $pr->image_medium_path, $pr->image_thumb_path] as $path) {
                if ($path) {
                    $paths[ltrim($path, '/')] = "productions#{$pr->id}";
                }
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function collectFilesOnDisk(string $disk): array
    {
        $files = [];

        foreach (['products', 'productions'] as $dir) {
            if (Storage::disk($disk)->exists($dir)) {
                $files = array_merge($files, Storage::disk($disk)->allFiles($dir));
            }
        }

        return $files;
    }
}
