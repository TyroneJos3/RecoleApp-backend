<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar foreign key de users -> vehiculos
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_vehicle_id')
                  ->references('id')
                  ->on('vehiculos')
                  ->onDelete('set null');
        });

        // Agregar foreign key de vehiculos -> users (current_driver_id)
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreign('current_driver_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });

        // Agregar foreign key de vehiculos -> users (user_id)
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_vehicle_id']);
        });

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['current_driver_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
