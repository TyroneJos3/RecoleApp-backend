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
        Schema::create('posiciones', function (Blueprint $table) {
            $table->id();

            $table->string('recorrido_id')->index();

            $table->decimal('lat', 10, 8)->comment('Latitud (-90 a 90)');
            $table->decimal('lon', 11, 8)->comment('Longitud (-180 a 180)');
            $table->timestamp('timestamp')->useCurrent()->index();

            $table->decimal('altitud', 8, 2)->nullable()->comment('Altitud en metros');
            $table->decimal('precision', 8, 2)->nullable()->comment('Precisión/Accuracy en metros');
            $table->decimal('velocidad', 8, 2)->nullable()->comment('Velocidad en km/h');
            $table->decimal('rumbo', 6, 2)->nullable()->comment('Rumbo/Heading en grados (0-360)');

            // Timestamps de Laravel (created_at, updated_at)
            $table->timestamps();
            $table->index(['recorrido_id', 'timestamp']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posiciones');
    }
};
