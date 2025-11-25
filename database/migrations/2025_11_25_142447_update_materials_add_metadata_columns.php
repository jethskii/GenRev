<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Only add if missing, so it is safe to run on an existing DB
            if (! Schema::hasColumn('materials', 'supplier_name')) {
                $table->string('supplier_name', 255)->nullable()->after('sku');
            }

            if (! Schema::hasColumn('materials', 'batch_code')) {
                $table->string('batch_code', 64)->nullable()->after('supplier_name');
            }

            if (! Schema::hasColumn('materials', 'storage_type')) {
                $table->string('storage_type', 50)->nullable()->after('batch_code');
            }

            if (! Schema::hasColumn('materials', 'manufactured_at')) {
                $table->date('manufactured_at')->nullable()->after('storage_type');
            }

            if (! Schema::hasColumn('materials', 'received_at')) {
                $table->date('received_at')->nullable()->after('manufactured_at');
            }

            if (! Schema::hasColumn('materials', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('received_at');
            }

            if (! Schema::hasColumn('materials', 'notes')) {
                $table->string('notes', 2000)->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }
            if (Schema::hasColumn('materials', 'batch_code')) {
                $table->dropColumn('batch_code');
            }
            if (Schema::hasColumn('materials', 'storage_type')) {
                $table->dropColumn('storage_type');
            }
            if (Schema::hasColumn('materials', 'manufactured_at')) {
                $table->dropColumn('manufactured_at');
            }
            if (Schema::hasColumn('materials', 'received_at')) {
                $table->dropColumn('received_at');
            }
            if (Schema::hasColumn('materials', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('materials', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};

