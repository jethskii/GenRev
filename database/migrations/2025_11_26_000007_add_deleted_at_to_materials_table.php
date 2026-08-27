<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App\Models\Material uses SoftDeletes, but create_materials_table never added
     * deleted_at - every soft-delete-aware query (e.g. whereNull('deleted_at')) fails
     * with "column materials.deleted_at does not exist".
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
