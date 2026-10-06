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
        Schema::create('reservasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamars')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('penghuni_id')->constrained('penghunis')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('tanggal_reservasi');
            $table->date('rencana_masuk');
            $table->date('rencana_keluar')->nullable();
            $table->decimal('uang_muka', 12, 2)->default(0);
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'dibatalkan'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservasis');
    }
};
