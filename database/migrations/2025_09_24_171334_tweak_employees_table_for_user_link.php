<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'status')) {
                $table->string('status', 20)->default('active')->after('username');
            } else {
                $table->string('status', 20)->default('active')->change();
            }

            // Ensure username & email are unique for clean lookups
            $table->unique('username', 'employees_username_unique');
            $table->unique('email', 'employees_email_unique');

            // Link to users (nullable, keep data if user is deleted)
            if (!Schema::hasColumn('employees', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('employees_username_unique');
            $table->dropUnique('employees_email_unique');
            // (optionally) $table->dropColumn('user_id'); // keep if you like
        });
    }
};
