<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePaymentLinkAudit extends Model
{
    protected $fillable = [
        'payment_id',
        'ledger_entry_id',
        'user_id',
        'action',
        'reason',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(Keuangan::class, 'ledger_entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
