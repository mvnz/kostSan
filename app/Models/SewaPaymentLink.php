<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SewaPaymentLink extends Model
{
    protected $fillable = [
        'sewa_id',
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
}
