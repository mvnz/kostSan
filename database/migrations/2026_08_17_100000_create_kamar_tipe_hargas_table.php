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
        Schema::create('kamar_tipe_hargas', function (Blueprint $table) {
            $table->id();
            $table->string('tipe')->unique();
            $table->decimal('harga_1_bulan', 12, 2)->default(0);
            $table->decimal('harga_3_bulan', 12, 2)->default(0);
            $table->decimal('harga_6_bulan', 12, 2)->default(0);
            $table->decimal('harga_12_bulan', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar_tipe_hargas');
    }
};
