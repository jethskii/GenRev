<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $usersTable = env('AUTH_TABLE', 'users');

        Schema::create($usersTable, function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('alt_email')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Auth
            $table->string('password');
            $table->rememberToken();

            // Role & profile
            $table->enum('role', ['admin','staff','manager','sales','inventory'])->default('staff');
            $table->string('website')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title', 160)->nullable();

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Helpful index
            $table->index('role');
        });
    }

    public function down(): void
    {
        $usersTable = env('AUTH_TABLE', 'users');
        Schema::dropIfExists($usersTable);
    }
};
