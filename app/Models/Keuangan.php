<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keuangan extends Model
{
    protected $fillable = [
        'payment_id',
        'manually_linked_at',
        'manually_linked_by',
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

    public function reversalSource(): HasOne
    {
        return $this->hasOne(PaymentReversal::class, 'reversal_entry_id');
    }

    public function linkAudits(): HasMany
    {
        return $this->hasMany(FinancePaymentLinkAudit::class, 'ledger_entry_id');
    }

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'manually_linked_at' => 'datetime',
    ];
}
