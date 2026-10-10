<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservasis', function (Blueprint $table): void {
            $table->foreignId('sewa_id')
                ->nullable()
                ->unique()
                ->after('penghuni_id')
                ->constrained('sewas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservasis', function (Blueprint $table): void {
            $table->dropUnique('reservasis_sewa_id_unique');
            $table->dropForeign(['sewa_id']);
        });

        Schema::table('reservasis', function (Blueprint $table): void {
            $table->dropColumn('sewa_id');
        });
    }
};
