<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sale_id'); // sales.id is INT UNSIGNED, not bigint
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_changes');
    }
};
