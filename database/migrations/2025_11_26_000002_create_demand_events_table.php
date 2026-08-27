<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('event_type'); // reservation | holiday | promo | other
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('reserved_qty', 12, 3)->nullable();
            $table->string('unit_type', 50)->nullable();
            $table->string('status', 30)->default('planned'); // planned | confirmed | cancelled | fulfilled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_events');
    }
};
