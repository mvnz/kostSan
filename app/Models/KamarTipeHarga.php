<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KamarTipeHarga extends Model
{
    protected $fillable = [
        'tipe',
        'harga_1_bulan',
        'harga_3_bulan',
        'harga_6_bulan',
        'harga_12_bulan',
    ];

    protected $casts = [
        'harga_1_bulan' => 'decimal:2',
        'harga_3_bulan' => 'decimal:2',
        'harga_6_bulan' => 'decimal:2',
        'harga_12_bulan' => 'decimal:2',
    ];
}
