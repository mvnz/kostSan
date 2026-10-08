<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keuangan extends Model
{
    protected $fillable = [
        'payment_id',
        'tanggal',
        'jenis',
        'kategori',
        'deskripsi',
        'jumlah',
        'bukti_path',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'payment_id');
    }

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];
}
