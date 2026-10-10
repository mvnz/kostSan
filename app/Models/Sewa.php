<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function convertedReservation(): HasOne
    {
        return $this->hasOne(Reservasi::class);
    }

    public function billingMonths(): int
    {
        if ($this->tanggal_masuk && $this->tanggal_keluar) {
            // Preserve existing partial-period pricing; recognize exact calendar terms.
            $months = ($this->tanggal_keluar->year - $this->tanggal_masuk->year) * 12
                + $this->tanggal_keluar->month - $this->tanggal_masuk->month;
            if ($months > 0 && $this->tanggal_masuk->copy()->addMonthsNoOverflow($months)->equalTo($this->tanggal_keluar)) {
                return $months;
            }

            return max(1, (int) $this->tanggal_masuk->diffInMonths($this->tanggal_keluar));
        }

        return max(1, (int) ($this->penghuni?->lama_sewa_bulan ?? 1));
    }
}
