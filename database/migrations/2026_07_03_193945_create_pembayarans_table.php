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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewa_id')->constrained('sewas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('periode');
            $table->date('tanggal_bayar')->nullable();
            $table->enum('metode', ['cash', 'transfer', 'e-wallet'])->default('transfer');
            $table->decimal('jumlah', 12, 2);
            $table->enum('status', ['lunas', 'belum_lunas'])->default('belum_lunas');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
