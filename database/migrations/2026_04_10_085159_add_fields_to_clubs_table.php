<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('description');
            $table->string('invitation_code')->unique()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn(['description', 'logo_path', 'invitation_code']);
        });
    }
};
