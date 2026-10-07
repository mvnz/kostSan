<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('payment_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('pembayarans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        // Link only unambiguous legacy AUTO invoices. Ambiguous rows remain
        // unlinked so a migration never guesses which financial record owns them.
        DB::table('invoices')
            ->whereNull('payment_id')
            ->where('keterangan', 'like', 'AUTO:%')
            ->orderBy('id')
            ->eachById(function (object $invoice): void {
                $payments = DB::table('pembayarans')
                    ->join('sewas', 'sewas.id', '=', 'pembayarans.sewa_id')
                    ->where('sewas.penghuni_id', $invoice->penghuni_id)
                    ->whereDate('pembayarans.periode', $invoice->periode)
                    ->pluck('pembayarans.id');

                if ($payments->count() === 1) {
                    DB::table('invoices')->where('id', $invoice->id)->update([
                        'payment_id' => $payments->first(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['payment_id']);
            $table->dropConstrainedForeignId('payment_id');
        });
    }
};
