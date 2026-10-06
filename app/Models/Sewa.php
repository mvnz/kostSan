<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Sewa extends Model
{
    protected $fillable = [
        'kamar_id',
        'penghuni_id',
        'tanggal_masuk',
        'tanggal_keluar',
        'biaya_bulanan',
        'uang_jaminan',
        'cara_pembayaran',
        'bukti_pembayaran',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_keluar' => 'date',
        'biaya_bulanan' => 'decimal:2',
        'uang_jaminan' => 'decimal:2',
    ];

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class);
    }

    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Penghuni::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }
}
