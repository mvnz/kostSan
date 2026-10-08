<?php

namespace App\Services;

use App\Models\User;

class AccessLanding
{
    public function routeName(User $user): ?string
    {
        $destinations = [
            'dashboard' => 'dashboard',
            'manajemen_sewa.sewa_kamar' => 'kamars.sewa',
            'manajemen_sewa.data_sewa' => 'sewas.index',
            'keuangan.data_keuangan' => 'keuangans.index',
            'keuangan.laporan_keuangan' => 'laporan-keuangan.index',
            'keuangan.laporan_hunian' => 'laporan-hunian.index',
            'keuangan.invoice' => 'invoices.index',
            'master_data.data_penghuni' => 'penghunis.index',
            'master_data.data_kamar' => 'kamars.index',
            'master_data.data_lantai' => 'kamar-floors.index',
            'pengaturan.profil_kost' => 'profil-kost.profile',
            'pengaturan.tipe_kamar' => 'kamar-tipe-hargas.index',
            'pengaturan.notifikasi_wa' => 'profil-kost.notification',
            'manajemen_akses.role' => 'roles.index',
            'manajemen_akses.user' => 'users.index',
            'sistem.log_aktivitas' => 'activity-logs.index',
        ];

        foreach ($destinations as $menu => $route) {
            if ($user->hasMenuPermission($menu, 'view')) {
                return $route;
            }
        }

        return null;
    }
}
