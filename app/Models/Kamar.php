<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    protected $fillable = [
        'nomor',
        'tipe',
        'harga_bulanan',
        'status',
        'layout_floor',
        'layout_row',
        'layout_col',
        'deskripsi',
    ];

    protected $casts = [
        'harga_bulanan' => 'decimal:2',
        'layout_floor' => 'integer',
        'layout_row' => 'integer',
        'layout_col' => 'integer',
    ];

    public function sewas(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }

    public function reservasis(): HasMany
    {
        return $this->hasMany(Reservasi::class);
    }
}
