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
        Schema::create('recorridos', function (Blueprint $table) {
            $table->id();

            $table->string('recorrido_remoto_id')->unique()->index();

            $table->string('ruta_id')->index();
            $table->string('vehiculo_id')->index();
            $table->string('conductor_id')->index();
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->decimal('distancia_total', 10, 2)->default(0)->comment('Distancia en kilómetros');
            $table->integer('duracion')->default(0)->comment('Duración en segundos');
            $table->integer('puntos_totales')->default(0)->comment('Total de puntos GPS capturados');

            $table->boolean('activo')->default(true)->index();
            $table->json('datos_adicionales')->nullable()->comment('Cualquier dato extra del recorrido');

            $table->timestamps();
            $table->index(['conductor_id', 'activo']);
            $table->index(['vehiculo_id', 'activo']);
            $table->index(['inicio', 'fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recorridos');
    }
};
