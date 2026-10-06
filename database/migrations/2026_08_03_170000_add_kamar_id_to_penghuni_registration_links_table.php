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
        Schema::table('penghuni_registration_links', function (Blueprint $table) {
            $table->foreignId('kamar_id')->nullable()->after('id')->constrained('kamars')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghuni_registration_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kamar_id');
        });
    }
};
