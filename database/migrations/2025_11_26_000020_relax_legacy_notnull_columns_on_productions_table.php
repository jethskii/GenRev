<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * product_name and quantity_produced are from the original create_productions_table, but
     * App\Models\Production's actual fillable uses product_name_snapshot and quantity instead -
     * neither of the original two is ever set by the app, yet both were NOT NULL with no default.
     */
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('product_name')->nullable()->change();
            $table->unsignedInteger('quantity_produced')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op: don't reintroduce constraints the app doesn't satisfy.
    }
};
