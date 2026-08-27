<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App\Models\SaleAudit's real fillable is [sale_id, order_item_id, message, at] and
     * Sale::audit() calls $this->audits()->create(['message' => ..., 'at' => now()]) - none of
     * order_item_id/message/at existed on sale_audits (it was built from a stray, out-of-date
     * copy of this model that a corrupted migration file happened to contain).
     */
    public function up(): void
    {
        Schema::table('sale_audits', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_audits', 'order_item_id')) {
                $table->unsignedBigInteger('order_item_id')->nullable();
            }
            if (!Schema::hasColumn('sale_audits', 'message')) {
                $table->text('message')->nullable();
            }
            if (!Schema::hasColumn('sale_audits', 'at')) {
                $table->timestamp('at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_audits', function (Blueprint $table) {
            foreach (['order_item_id', 'message', 'at'] as $col) {
                if (Schema::hasColumn('sale_audits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
