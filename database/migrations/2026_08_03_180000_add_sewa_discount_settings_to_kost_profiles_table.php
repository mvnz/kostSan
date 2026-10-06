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
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->decimal('diskon_sewa_1_bulan', 12, 2)->default(0)->after('whatsapp_default_country_code');
            $table->decimal('diskon_sewa_3_bulan', 12, 2)->default(0)->after('diskon_sewa_1_bulan');
            $table->decimal('diskon_sewa_6_bulan', 12, 2)->default(0)->after('diskon_sewa_3_bulan');
            $table->decimal('diskon_sewa_12_bulan', 12, 2)->default(0)->after('diskon_sewa_6_bulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'diskon_sewa_1_bulan',
                'diskon_sewa_3_bulan',
                'diskon_sewa_6_bulan',
                'diskon_sewa_12_bulan',
            ]);
        });
    }
};
