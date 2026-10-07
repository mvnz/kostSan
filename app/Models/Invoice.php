<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'payment_id',
        'penghuni_id',
        'nomor_invoice',
        'periode',
        'jatuh_tempo',
        'jumlah_tagihan',
        'status',
        'tanggal_kirim',
        'keterangan',
    ];

    protected $casts = [
        'periode' => 'date',
        'jatuh_tempo' => 'date',
        'tanggal_kirim' => 'datetime',
        'jumlah_tagihan' => 'decimal:2',
    ];

    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Penghuni::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'payment_id');
    }
}
