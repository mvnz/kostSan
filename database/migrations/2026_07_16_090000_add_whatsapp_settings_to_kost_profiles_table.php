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
            $table->boolean('whatsapp_enabled')->default(false)->after('jumlah_kamar');
            $table->string('whatsapp_provider', 50)->default('fonnte')->after('whatsapp_enabled');
            $table->string('whatsapp_base_url')->nullable()->after('whatsapp_provider');
            $table->string('whatsapp_token')->nullable()->after('whatsapp_base_url');
            $table->unsignedSmallInteger('whatsapp_timeout')->default(10)->after('whatsapp_token');
            $table->string('whatsapp_default_country_code', 10)->default('62')->after('whatsapp_timeout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_enabled',
                'whatsapp_provider',
                'whatsapp_base_url',
                'whatsapp_token',
                'whatsapp_timeout',
                'whatsapp_default_country_code',
            ]);
        });
    }
};
