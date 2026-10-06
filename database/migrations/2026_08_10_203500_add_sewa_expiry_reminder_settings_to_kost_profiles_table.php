<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('whatsapp_sewa_habis_reminder_days')
                ->default(7)
                ->after('whatsapp_default_country_code');
            $table->boolean('whatsapp_sewa_habis_repeat_until_paid')
                ->default(true)
                ->after('whatsapp_sewa_habis_reminder_days');
        });
    }

    public function down(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_sewa_habis_reminder_days',
                'whatsapp_sewa_habis_repeat_until_paid',
            ]);
        });
    }
};
