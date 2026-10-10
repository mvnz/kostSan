<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewa_payment_links', function (Blueprint $table): void {
            $table->foreignId('payment_id')->nullable()->after('sewa_id')->constrained('pembayarans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->index(['payment_id', 'used_at'], 'payment_links_payment_usage');
        });
    }

    public function down(): void
    {
        Schema::table('sewa_payment_links', function (Blueprint $table): void {
            $table->dropIndex('payment_links_payment_usage');
            $table->dropConstrainedForeignId('payment_id');
        });
    }
};
