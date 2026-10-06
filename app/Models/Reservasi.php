<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $fillable = [
        'kamar_id',
        'penghuni_id',
        'tanggal_reservasi',
        'rencana_masuk',
        'rencana_keluar',
        'uang_muka',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_reservasi' => 'date',
        'rencana_masuk' => 'date',
        'rencana_keluar' => 'date',
        'uang_muka' => 'decimal:2',
    ];

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class);
    }

    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Penghuni::class);
    }
}
