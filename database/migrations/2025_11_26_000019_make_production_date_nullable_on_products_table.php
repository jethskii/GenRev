<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * products.production_date is NOT NULL with no default from create_products_table, but
     * ProductController never sets it when creating a product (it's only ever used for
     * ordering the productions relationship) - a holdover from before products/productions
     * were split into separate tables/concepts.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('production_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op: don't reintroduce a constraint the app doesn't satisfy.
    }
};
