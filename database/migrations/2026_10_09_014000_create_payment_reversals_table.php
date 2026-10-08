<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained('pembayarans')->restrictOnDelete();
            $table->foreignId('original_entry_id')->constrained('keuangans')->restrictOnDelete();
            $table->foreignId('reversal_entry_id')->unique()->constrained('keuangans')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('reversal_date');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
    }
};
