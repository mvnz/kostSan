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
        Schema::create('sewas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamars')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('penghuni_id')->constrained('penghunis')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->decimal('biaya_bulanan', 12, 2);
            $table->decimal('uang_jaminan', 12, 2)->default(0);
            $table->enum('status', ['aktif', 'selesai', 'menunggak'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sewas');
    }
};
