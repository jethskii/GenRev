<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * variant_name/variant/type/product_type are intentionally NOT added here - the model's
     * own comment marks them "optional legacy/fallback columns (do not create if you don't
     * have them)" and its code already guards their absence safely. unit_type/type_label/notes
     * are not optional: unit_type in particular is unconditionally written back in the
     * saving() event, so a missing column there fatals every save.
     */
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'unit_type')) {
                $table->string('unit_type', 20)->nullable();
            }
            if (!Schema::hasColumn('sales_order_items', 'type_label')) {
                $table->string('type_label')->nullable();
            }
            if (!Schema::hasColumn('sales_order_items', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            foreach (['unit_type', 'type_label', 'notes'] as $col) {
                if (Schema::hasColumn('sales_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
