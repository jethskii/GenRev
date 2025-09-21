<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            // helpful indexes if not present
            if (!Schema::hasColumn('employees','email'))    $table->string('email',190)->nullable()->index();
            if (!Schema::hasColumn('employees','username')) $table->string('username',120)->nullable()->unique();
            if (!Schema::hasColumn('employees','status'))   $table->enum('status',['active','inactive'])->default('active')->index();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees','user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
