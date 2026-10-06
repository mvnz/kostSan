<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->text('whatsapp_notification_types')->nullable();
            $table->text('whatsapp_message_templates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('kost_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_notification_types',
                'whatsapp_message_templates',
            ]);
        });
    }
};
