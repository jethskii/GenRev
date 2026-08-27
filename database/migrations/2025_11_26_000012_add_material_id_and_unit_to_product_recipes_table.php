<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App\Models\ProductRecipe (and several controllers) reference product_recipes.material_id
     * as a newer alias of the original ingredient_id column, with explicit sync logic
     * (ProductRecipeController@syncMaterialIngredientColumns) keeping both in step. material_id
     * never actually existed, so every direct read/write/relationship through it failed.
     */
    public function up(): void
    {
        Schema::table('product_recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_recipes', 'material_id')) {
                $table->unsignedBigInteger('material_id')->nullable()->after('ingredient_id');
                $table->foreign('material_id')->references('id')->on('materials')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_recipes', 'unit')) {
                $table->string('unit', 20)->nullable();
            }
        });

        // Backfill material_id from the existing ingredient_id data
        Schema::table('product_recipes', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::table('product_recipes')
                ->whereNull('material_id')
                ->whereNotNull('ingredient_id')
                ->update(['material_id' => \Illuminate\Support\Facades\DB::raw('ingredient_id')]);
        });
    }

    public function down(): void
    {
        Schema::table('product_recipes', function (Blueprint $table) {
            if (Schema::hasColumn('product_recipes', 'material_id')) {
                $table->dropForeign(['material_id']);
                $table->dropColumn('material_id');
            }
            if (Schema::hasColumn('product_recipes', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
