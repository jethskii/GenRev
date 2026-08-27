<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('employees', function (Blueprint $table) {
        if (!Schema::hasColumn('employees', 'user_id')) {
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
        }
    });

    $hasFk = collect(Schema::getForeignKeys('employees'))->pluck('name')->contains('employees_user_id_foreign');
    if (!$hasFk) {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}

public function down()
{
    Schema::table('employees', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });

    }
};
