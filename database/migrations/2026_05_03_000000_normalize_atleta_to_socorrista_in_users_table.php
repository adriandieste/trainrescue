<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Normaliza el rol 'atleta' al nombre canónico 'socorrista'.
     */
    public function up(): void
    {
        // Actualizar todos los registros que tengan rol 'atleta' a 'socorrista'
        DB::table('users')
            ->where('rol', 'atleta')
            ->update(['rol' => 'socorrista']);

        // Cambiar el valor por defecto de la columna
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('socorrista')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('rol', 'socorrista')
            ->update(['rol' => 'atleta']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('atleta')->change();
        });
    }
};

