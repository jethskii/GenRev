<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * employee_id is a legacy identifier from the original create_employees_table migration:
     * NOT NULL + unique, but nothing in the current App\Models\Employee (not in $fillable, not
     * referenced anywhere in the app) ever sets it. Every Employee::create(...) call - which is
     * how the app actually creates employees - fails with a not-null violation. Nothing reads it
     * either, so relaxing the constraint doesn't remove functionality that exists today.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op: don't reintroduce a NOT NULL constraint nothing populates.
    }
};
