<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewaPaymentLink extends Model
{
    protected $fillable = [
        'sewa_id',
        'payment_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->used_at !== null || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function sewa(): BelongsTo
    {
        return $this->belongsTo(Sewa::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'payment_id');
    }
}
