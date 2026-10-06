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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penghuni_id')->constrained('penghunis')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nomor_invoice')->unique();
            $table->date('periode');
            $table->date('jatuh_tempo');
            $table->decimal('jumlah_tagihan', 12, 2);
            $table->enum('status', ['draft', 'terkirim', 'lunas'])->default('draft');
            $table->timestamp('tanggal_kirim')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
