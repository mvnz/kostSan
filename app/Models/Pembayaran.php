<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Pembayaran extends Model
{
    protected $fillable = [
        'sewa_id',
        'coverage_start',
        'coverage_end',
        'overlap_override_reason',
        'periode',
        'tanggal_bayar',
        'metode',
        'jumlah',
        'status',
        'keterangan',
        'bukti_pembayaran_path',
    ];

    protected $casts = [
        'periode' => 'date',
        'coverage_start' => 'date',
        'coverage_end' => 'date',
        'tanggal_bayar' => 'date',
        'jumlah' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $pembayaran): void {
            $pembayaran->syncInvoiceFromPayment();
        });
    }

    public function sewa(): BelongsTo
    {
        return $this->belongsTo(Sewa::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'payment_id');
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(Keuangan::class, 'payment_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(PaymentReversal::class, 'payment_id');
    }

    public function syncInvoiceFromPayment(): void
    {
        $this->loadMissing('sewa.kamar', 'sewa.penghuni');

        $penghuniId = $this->sewa?->penghuni_id;
        $periode = $this->periode?->toDateString();

        if (! $penghuniId || ! $periode) {
            return;
        }

        $kamarNomor = $this->sewa?->kamar?->nomor ?? '-';
        $keterangan = 'AUTO: invoice dari pembayaran sewa kamar '.$kamarNomor;

        $invoice = Invoice::firstOrNew(['payment_id' => $this->id]);
        $invoice->fill([
            'penghuni_id' => $penghuniId,
            'jatuh_tempo' => $this->tanggal_bayar ?? $this->periode,
            'periode' => $periode,
            'jumlah_tagihan' => $this->jumlah,
            'keterangan' => $keterangan,
        ]);
        if (! $invoice->exists) {
            $invoice->nomor_invoice = 'INV-KOS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
            $invoice->status = 'draft';
        }
        if ($this->status === 'lunas') {
            $invoice->status = 'lunas';
        }
        $invoice->save();
    }
}
