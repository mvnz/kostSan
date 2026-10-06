<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pembayaran extends Model
{
    protected $fillable = [
        'sewa_id',
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

    public function syncInvoiceFromPayment(): void
    {
        $this->loadMissing('sewa.kamar', 'sewa.penghuni');

        $penghuniId = $this->sewa?->penghuni_id;
        $periode = $this->periode?->toDateString();

        if (!$penghuniId || !$periode) {
            return;
        }

        $kamarNomor = $this->sewa?->kamar?->nomor ?? '-';
        $keterangan = 'AUTO: invoice dari pembayaran sewa kamar ' . $kamarNomor;

        $invoice = Invoice::query()
            ->where('penghuni_id', $penghuniId)
            ->whereDate('periode', $periode)
            ->first();

        if ($invoice === null) {
            Invoice::create([
                'penghuni_id' => $penghuniId,
                'nomor_invoice' => 'INV-KOS-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'periode' => $periode,
                'jatuh_tempo' => $this->tanggal_bayar ?? $this->periode,
                'jumlah_tagihan' => $this->jumlah,
                'status' => $this->status === 'lunas' ? 'lunas' : 'draft',
                'keterangan' => $keterangan,
            ]);

            return;
        }

        $invoice->update([
            'jatuh_tempo' => $this->tanggal_bayar ?? $this->periode,
            'jumlah_tagihan' => $this->jumlah,
            'status' => $this->status === 'lunas' ? 'lunas' : $invoice->status,
            'keterangan' => $keterangan,
        ]);
    }
}
