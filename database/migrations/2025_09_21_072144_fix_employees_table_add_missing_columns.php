<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the table if it doesn't exist
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Link to users (nullable; will fill on registration if you wired it)
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // Core profile
                $table->string('first_name', 120);
                $table->string('last_name', 120);
                $table->string('position', 160)->nullable();

                // Contacts / login handle for admin UI
                $table->string('email', 190)->nullable()->index();
                $table->string('username', 120)->unique();

                // If you keep a separate employee password (optional in your app)
                $table->string('password')->nullable();

                // Use string for wide compatibility (SQLite has no native ENUM)
                $table->string('status', 20)->default('active')->index();

                // Media
                $table->string('avatar_path')->nullable();

                $table->timestamps();
                // If you want soft deletes in the future, uncomment:
                // $table->softDeletes();
            });
            return;
        }

        // If table exists, add any missing columns safely
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'first_name')) {
                $table->string('first_name', 120)->after('user_id');
            }
            if (!Schema::hasColumn('employees', 'last_name')) {
                $table->string('last_name', 120)->after('first_name');
            }
            if (!Schema::hasColumn('employees', 'position')) {
                $table->string('position', 160)->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('employees', 'email')) {
                $table->string('email', 190)->nullable()->after('position')->index();
            }
            if (!Schema::hasColumn('employees', 'username')) {
                $table->string('username', 120)->after('email');
            }
            if (!Schema::hasColumn('employees', 'password')) {
                $table->string('password')->nullable()->after('username');
            }
            if (!Schema::hasColumn('employees', 'status')) {
                $table->string('status', 20)->default('active')->after('password')->index();
            }
            if (!Schema::hasColumn('employees', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('employees', 'created_at')) {
                $table->timestamps();
            }
        });

        // Unique index for username if it wasn't unique
        if (!Schema::hasColumn('employees', 'username')) {
            // handled above
        } else {
            // Try to add a unique index if it doesn't exist yet (safe for MySQL; SQLite ignores name)
            Schema::table('employees', function (Blueprint $table) {
                try { $table->unique('username'); } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        // We won't drop columns in down() to avoid accidental data loss.
        // If you really need to revert, write a separate migration tailored to your environment.
    }
};
