<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            return;
        }

        Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('position')->nullable();
    $table->string('email')->nullable()->index();
    $table->string('username')->unique();
    $table->string('password')->nullable();
    $table->string('status')->default('active');
    $table->string('avatar_path')->nullable();
    $table->string('phone')->nullable();
    $table->date('hire_date')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
