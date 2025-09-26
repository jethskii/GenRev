<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $usersTable = env('AUTH_TABLE', 'users');

        Schema::table('employees', function (Blueprint $table) use ($usersTable) {
            // Ensure user_id exists and is the right type
            if (!Schema::hasColumn('employees', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }

            // Indexes (ignore if they already exist in your DB)
            if (! $this->hasIndex('employees', 'employees_user_id_index')) {
                $table->index('user_id', 'employees_user_id_index');
            }
            if (Schema::hasColumn('employees', 'username') && ! $this->hasIndex('employees', 'employees_username_unique')) {
                $table->unique('username', 'employees_username_unique');
            }
            if (Schema::hasColumn('employees', 'email') && ! $this->hasIndex('employees', 'employees_email_index')) {
                $table->index('email', 'employees_email_index');
            }

            // Drop old FK if present (name may vary)
            $this->dropForeignIfExists('employees', 'employees_user_id_foreign');

            // Recreate FK to the configured users table
            $table->foreign('user_id', 'employees_user_id_foreign')
                  ->references('id')->on($usersTable)
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $usersTable = env('AUTH_TABLE', 'users');

        Schema::table('employees', function (Blueprint $table) use ($usersTable) {
            $this->dropForeignIfExists('employees', 'employees_user_id_foreign');
            // keep column; just removing the FK is safer for rollbacks
        });
    }

    /** Helpers (scoped to this anonymous class) */
    private function hasIndex(string $table, string $index): bool
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($table);
        return $doctrineTable->hasIndex($index);
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        $connection = Schema::getConnection();
        $schema = $connection->getDoctrineSchemaManager();
        $doctrineTable = $schema->listTableDetails($table);

        if ($doctrineTable->hasForeignKey($name)) {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropForeign($name);
            });
        }
    }
};
