<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->timestamp('manually_linked_at')->nullable()->after('payment_id');
            $table->foreignId('manually_linked_by')->nullable()->after('manually_linked_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('finance_payment_link_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('pembayarans')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained('keuangans')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_link_audits');
        Schema::table('keuangans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manually_linked_by');
            $table->dropColumn('manually_linked_at');
        });
    }
};
