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
            $table->string('vehiculo_id')->nullable()->index(); // ID de serverProfe
            $table->string('placa')->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
           // $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Usuario local
            $table->string('perfil_id'); // UUID del perfil de serverProfe
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
