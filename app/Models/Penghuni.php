<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Penghuni extends Model
{
    protected $casts = [
        'tanggal_lahir' => 'date',
        'last_birthday_notified_on' => 'date',
        'tanggal_mulai_tinggal' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'setuju_peraturan' => 'boolean',
    ];

    protected $fillable = [
        'nama',
        'nomor_kamar',
        'jumlah_kunci',
        'tempat_lahir',
        'tanggal_lahir',
        'last_birthday_notified_on',
        'nik',
        'telepon',
        'email',
        'alamat_ktp',
        'pekerjaan',
        'alamat',
        'tanggal_mulai_tinggal',
        'lama_sewa_bulan',
        'kontak_darurat_nama',
        'kontak_darurat_hubungan',
        'kontak_darurat_telepon',
        'jenis_kendaraan',
        'kendaraan_merek_tipe',
        'kendaraan_warna',
        'kendaraan_nomor_polisi',
        'harga_sewa',
        'tanggal_jatuh_tempo',
        'foto_ktp_path',
        'foto_selfie_path',
        'setuju_peraturan',
        'catatan_pengelola',
    ];

    public function sewas(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }

    public function reservasis(): HasMany
    {
        return $this->hasMany(Reservasi::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
