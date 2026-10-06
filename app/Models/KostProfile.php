<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KostProfile extends Model
{
    protected $fillable = [
        'nama_kost',
        'pemilik',
        'telepon',
        'email',
        'alamat',
        'deskripsi',
        'jumlah_kamar',
        'whatsapp_enabled',
        'whatsapp_provider',
        'whatsapp_base_url',
        'whatsapp_token',
        'whatsapp_timeout',
        'whatsapp_default_country_code',
        'whatsapp_notification_types',
        'whatsapp_message_templates',
        'whatsapp_sewa_habis_reminder_days',
        'whatsapp_sewa_habis_repeat_until_paid',
        'diskon_sewa_1_bulan',
        'diskon_sewa_3_bulan',
        'diskon_sewa_6_bulan',
        'diskon_sewa_12_bulan',
    ];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
        'whatsapp_token' => 'encrypted',
        'whatsapp_notification_types' => 'array',
        'whatsapp_message_templates' => 'array',
        'whatsapp_sewa_habis_repeat_until_paid' => 'boolean',
        'diskon_sewa_1_bulan' => 'decimal:2',
        'diskon_sewa_3_bulan' => 'decimal:2',
        'diskon_sewa_6_bulan' => 'decimal:2',
        'diskon_sewa_12_bulan' => 'decimal:2',
    ];
}
