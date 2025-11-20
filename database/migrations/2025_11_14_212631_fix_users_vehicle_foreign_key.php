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
        // Si la columna current_vehicle_id ya existe pero sin foreign key
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->foreign('current_vehicle_id')
                      ->references('id')
                      ->on('vehiculos')
                      ->onDelete('set null');
            } catch (\Exception $e) {
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign(['current_vehicle_id']);
            } catch (\Exception $e) {
                // no hacer nada
            }
        });
    }
};
