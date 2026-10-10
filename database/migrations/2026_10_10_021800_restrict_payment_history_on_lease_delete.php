<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table): void {
            $table->dropForeign(['sewa_id']);
            $table->foreign('sewa_id')->references('id')->on('sewas')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table): void {
            $table->dropForeign(['sewa_id']);
            $table->foreign('sewa_id')->references('id')->on('sewas')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
