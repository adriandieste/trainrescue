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
        Schema::table('custom_exercises', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_sets')->default(3)->after('video_url');
            $table->unsignedSmallInteger('default_meters')->nullable()->after('default_sets');
            $table->unsignedSmallInteger('default_rest_seconds')->default(45)->after('default_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_exercises', function (Blueprint $table) {
            $table->dropColumn(['default_sets', 'default_meters', 'default_rest_seconds']);
        });
    }
};

