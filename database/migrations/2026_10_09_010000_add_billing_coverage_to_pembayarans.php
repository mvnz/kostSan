<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->date('coverage_start')->nullable();
            $table->date('coverage_end')->nullable();
            $table->index(['sewa_id', 'coverage_start', 'coverage_end'], 'payments_billing_coverage');
        });
    }

    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('payments_billing_coverage');
            $table->dropColumn(['coverage_start', 'coverage_end']);
        });
    }
};
