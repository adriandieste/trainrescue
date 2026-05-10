<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el valor por defecto de la columna `rol` y la hace nullable.
     * A partir de ahora los usuarios nuevos se crearán sin rol hasta que lo elijan
     * en el flujo de onboarding post-login.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Antes de revertir la nullable, rellenar valores null para evitar error de constraint
        DB::table('users')->whereNull('rol')->update(['rol' => 'socorrista']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->nullable(false)->default('socorrista')->change();
        });
    }
};



