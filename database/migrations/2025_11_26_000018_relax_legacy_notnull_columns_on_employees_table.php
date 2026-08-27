<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Three more NOT NULL columns from the original create_employees_table that the app's
     * actual create flow (EmployeeController::validatedData() + Employee::create()) never
     * supplies:
     *  - name: dead legacy column: not in $fillable, not read/written anywhere; the model
     *    computes a display name from first_name/last_name instead.
     *  - position: validated as 'nullable' in EmployeeController, but the column was NOT NULL.
     *  - password: never part of the validated/create payload at all (only a *linked user's*
     *    password gets hashed and set, on the users table, not here).
     * Every real employee creation was one bad request away from a not-null violation.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('position')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op: don't reintroduce constraints the app doesn't satisfy.
    }
};
