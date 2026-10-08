<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReversal extends Model
{
    protected $fillable = ['payment_id', 'original_entry_id', 'reversal_entry_id', 'reversed_by', 'reversal_date', 'reason'];

    protected $casts = ['reversal_date' => 'date'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(Keuangan::class, 'original_entry_id');
    }

    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(Keuangan::class, 'reversal_entry_id');
    }
}
