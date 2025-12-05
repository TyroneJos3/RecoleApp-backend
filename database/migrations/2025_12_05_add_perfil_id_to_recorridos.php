<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recorridos', function (Blueprint $table) {
            $table->string('perfil_id')->nullable()->after('conductor_id')->comment('UUID del perfil de la API del profesor');
        });
    }

    public function down(): void
    {
        Schema::table('recorridos', function (Blueprint $table) {
            $table->dropColumn('perfil_id');
        });
    }
};
