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
        Schema::create('asignacion_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehiculos')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('assignment_date')->useCurrent();
            $table->timestamp('unassignment_date')->nullable();
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            // Asegurar que un vehículo solo tenga una asignación activa
            $table->unique(['vehicle_id', 'status'])->where('status', 'active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_vehiculo');
    }
};
