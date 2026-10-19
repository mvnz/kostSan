<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservasis', function (Blueprint $table): void {
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'dibatalkan', 'dikonversi', 'kedaluwarsa'])
                ->default('menunggu')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('reservasis')->whereIn('status', ['dikonversi', 'kedaluwarsa'])->exists()) {
            throw new RuntimeException('Rollback status reservasi ditolak: masih ada status dikonversi atau kedaluwarsa. Pertahankan histori dan tinjau rencana rollback.');
        }

        Schema::table('reservasis', function (Blueprint $table): void {
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'dibatalkan'])->default('menunggu')->change();
        });
    }
};
