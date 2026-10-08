<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->foreignId('payment_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('pembayarans')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
        });
        Schema::table('keuangans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_id');
        });
    }
};
