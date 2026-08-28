<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * products.product_name had no database-level uniqueness at all - only an application-level
     * Rule::unique() check in ProductController, meaning two concurrent "create product" requests
     * with the same name could both pass validation and both insert (no DB constraint to catch
     * the race). Adds real enforcement on Postgres via a partial unique index that excludes
     * soft-deleted rows, so archiving a product still allows reusing its name (a plain unique
     * index would silently reintroduce that bug, since the old row physically still exists).
     *
     * MySQL has no partial/filtered index support, so this only adds real protection on Postgres
     * (this app's actual production database). MySQL keeps relying on the app-level check alone,
     * same as before - not a regression, just not strengthened further here.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX products_product_name_unique ON products (product_name) WHERE deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_product_name_unique');
        }
    }
};
