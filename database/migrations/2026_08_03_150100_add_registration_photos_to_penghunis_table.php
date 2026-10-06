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
        Schema::table('penghunis', function (Blueprint $table) {
            $table->string('foto_ktp_path')->nullable()->after('tanggal_jatuh_tempo');
            $table->string('foto_selfie_path')->nullable()->after('foto_ktp_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghunis', function (Blueprint $table) {
            $table->dropColumn(['foto_ktp_path', 'foto_selfie_path']);
        });
    }
};
