<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('vehiculo_id')->nullable()->index();
            $table->string('placa')->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();

            // campos nuevos
            $table->boolean('activo')->default(true);
            $table->foreignId('current_driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('available');
            $table->integer('capacity')->nullable();
            $table->integer('year')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('perfil_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
