<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    // Maps route name → [menuKey, action]
    protected array $map = [
        'dashboard'                          => ['dashboard', 'view'],

        // Kamar
        'kamars.index'                       => ['master_data.data_kamar', 'view'],
        'kamars.show'                        => ['master_data.data_kamar', 'view'],
        'kamars.create'                      => ['master_data.data_kamar', 'create'],
        'kamars.store'                       => ['master_data.data_kamar', 'create'],
        'kamars.edit'                        => ['master_data.data_kamar', 'update'],
        'kamars.update'                      => ['master_data.data_kamar', 'update'],
        'kamars.destroy'                     => ['master_data.data_kamar', 'delete'],
        'kamars.update-layout'               => ['master_data.data_kamar', 'update'],
        'kamars.selesai-sewa'                => ['master_data.data_kamar', 'update'],
        'kamars.perpanjang-sewa'             => ['master_data.data_kamar', 'update'],

        // Sewa Kamar (status kamar)
        'kamars.sewa'                        => ['manajemen_sewa.sewa_kamar', 'view'],

        // Kamar Lantai
        'kamar-floors.index'                 => ['master_data.data_lantai', 'view'],
        'kamar-floors.store'                 => ['master_data.data_lantai', 'create'],
        'kamar-floors.update'                => ['master_data.data_lantai', 'update'],
        'kamar-floors.destroy'               => ['master_data.data_lantai', 'delete'],

        // Tipe Kamar
        'kamar-tipe-hargas.index'             => ['pengaturan.tipe_kamar', 'view'],
        'kamar-tipe-hargas.store'             => ['pengaturan.tipe_kamar', 'create'],
        'kamar-tipe-hargas.update'            => ['pengaturan.tipe_kamar', 'update'],
        'kamar-tipe-hargas.destroy'           => ['pengaturan.tipe_kamar', 'delete'],

        // Penghuni
        'penghunis.index'                    => ['master_data.data_penghuni', 'view'],
        'penghunis.show'                     => ['master_data.data_penghuni', 'view'],
        'penghunis.cetak'                    => ['master_data.data_penghuni', 'view'],
        'penghunis.create'                   => ['master_data.data_penghuni', 'create'],
        'penghunis.store'                    => ['master_data.data_penghuni', 'create'],
        'penghuni-registrations.generate'    => ['master_data.data_penghuni', 'create'],
        'penghunis.edit'                     => ['master_data.data_penghuni', 'update'],
        'penghunis.update'                   => ['master_data.data_penghuni', 'update'],
        'penghunis.destroy'                  => ['master_data.data_penghuni', 'delete'],

        // Data Sewa
        'sewas.index'                        => ['manajemen_sewa.data_sewa', 'view'],
        'sewas.show'                         => ['manajemen_sewa.data_sewa', 'view'],
        'sewas.pilih-kamar'                  => ['manajemen_sewa.data_sewa', 'view'],
        'sewas.create'                       => ['manajemen_sewa.data_sewa', 'create'],
        'sewas.store'                        => ['manajemen_sewa.data_sewa', 'create'],
        'sewas.edit'                         => ['manajemen_sewa.data_sewa', 'update'],
        'sewas.update'                       => ['manajemen_sewa.data_sewa', 'update'],
        'sewas.destroy'                      => ['manajemen_sewa.data_sewa', 'delete'],
        'sewa-payment-registrations.generate' => ['manajemen_sewa.data_sewa', 'create'],

        // Pembayaran (bagian dari manajemen sewa)
        'pembayarans.index'                  => ['manajemen_sewa.data_sewa', 'view'],
        'pembayarans.export'                 => ['manajemen_sewa.data_sewa', 'view'],
        'pembayarans.show'                   => ['manajemen_sewa.data_sewa', 'view'],
        'pembayarans.create'                 => ['manajemen_sewa.data_sewa', 'create'],
        'pembayarans.store'                  => ['manajemen_sewa.data_sewa', 'create'],
        'pembayarans.edit'                   => ['manajemen_sewa.data_sewa', 'update'],
        'pembayarans.update'                 => ['manajemen_sewa.data_sewa', 'update'],
        'pembayarans.destroy'                => ['manajemen_sewa.data_sewa', 'delete'],
        'pembayarans.approve'                => ['manajemen_sewa.data_sewa', 'update'],

        // Reservasi (bagian dari manajemen sewa)
        'reservasis.index'                   => ['manajemen_sewa.data_sewa', 'view'],
        'reservasis.show'                    => ['manajemen_sewa.data_sewa', 'view'],
        'reservasis.create'                  => ['manajemen_sewa.data_sewa', 'create'],
        'reservasis.store'                   => ['manajemen_sewa.data_sewa', 'create'],
        'reservasis.edit'                    => ['manajemen_sewa.data_sewa', 'update'],
        'reservasis.update'                  => ['manajemen_sewa.data_sewa', 'update'],
        'reservasis.destroy'                 => ['manajemen_sewa.data_sewa', 'delete'],

        // Keuangan
        'keuangans.export'                   => ['keuangan.data_keuangan', 'view'],
        'keuangans.reconciliation'           => ['keuangan.data_keuangan', 'view'],
        'keuangans.index'                    => ['keuangan.data_keuangan', 'view'],
        'keuangans.show'                     => ['keuangan.data_keuangan', 'view'],
        'keuangans.create'                   => ['keuangan.data_keuangan', 'create'],
        'keuangans.store'                    => ['keuangan.data_keuangan', 'create'],
        'keuangans.edit'                     => ['keuangan.data_keuangan', 'update'],
        'keuangans.update'                   => ['keuangan.data_keuangan', 'update'],
        'keuangans.destroy'                  => ['keuangan.data_keuangan', 'delete'],

        // Laporan Keuangan
        'laporan-keuangan.index'             => ['keuangan.laporan_keuangan', 'view'],
        'laporan-keuangan.pdf'               => ['keuangan.laporan_keuangan', 'view'],

        // Laporan Hunian
        'laporan-hunian.index'               => ['keuangan.laporan_hunian', 'view'],

        // Kontrak Sewa
        'sewas.kontrak'                      => ['manajemen_sewa.data_sewa', 'view'],

        // Bulk Billing
        'pembayarans.bulk-billing'           => ['manajemen_sewa.data_sewa', 'create'],
        'pembayarans.bulk-billing.store'     => ['manajemen_sewa.data_sewa', 'create'],

        // Invoice
        'invoices.reconciliation'            => ['keuangan.invoice', 'view'],
        'invoices.index'                     => ['keuangan.invoice', 'view'],
        'invoices.show'                      => ['keuangan.invoice', 'view'],
        'invoices.create'                    => ['keuangan.invoice', 'create'],
        'invoices.store'                     => ['keuangan.invoice', 'create'],
        'invoices.edit'                      => ['keuangan.invoice', 'update'],
        'invoices.update'                    => ['keuangan.invoice', 'update'],
        'invoices.destroy'                   => ['keuangan.invoice', 'delete'],
        'invoices.refresh-from-payments'     => ['keuangan.invoice', 'create'],
        'invoices.send'                      => ['keuangan.invoice', 'update'],

        // Pengaturan Kost
        'profil-kost.edit'                   => ['pengaturan.profil_kost', 'view'],
        'profil-kost.profile'                => ['pengaturan.profil_kost', 'view'],
        'profil-kost.profile.update'         => ['pengaturan.profil_kost', 'update'],
        'profil-kost.notification'           => ['pengaturan.notifikasi_wa', 'view'],
        'profil-kost.notification.update'    => ['pengaturan.notifikasi_wa', 'update'],

        // Manajemen Akses
        'roles.index'                        => ['manajemen_akses.role', 'view'],
        'roles.show'                         => ['manajemen_akses.role', 'view'],
        'roles.create'                       => ['manajemen_akses.role', 'create'],
        'roles.store'                        => ['manajemen_akses.role', 'create'],
        'roles.edit'                         => ['manajemen_akses.role', 'update'],
        'roles.update'                       => ['manajemen_akses.role', 'update'],
        'roles.destroy'                      => ['manajemen_akses.role', 'delete'],

        'users.index'                        => ['manajemen_akses.user', 'view'],
        'users.show'                         => ['manajemen_akses.user', 'view'],
        'users.create'                       => ['manajemen_akses.user', 'create'],
        'users.store'                        => ['manajemen_akses.user', 'create'],
        'users.edit'                         => ['manajemen_akses.user', 'update'],
        'users.update'                       => ['manajemen_akses.user', 'update'],
        'users.destroy'                      => ['manajemen_akses.user', 'delete'],

        // Log Aktivitas
        'activity-logs.index'                => ['sistem.log_aktivitas', 'view'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Tanpa role = superadmin, akses penuh
        if (!$user || !$user->role_id) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName || !isset($this->map[$routeName])) {
            return $next($request);
        }

        [$menuKey, $action] = $this->map[$routeName];

        if (!$user->hasMenuPermission($menuKey, $action)) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki akses ke fitur ini.');
            }

            return redirect()->route('dashboard')->with(
                'error',
                'Anda tidak memiliki akses ke halaman tersebut.'
            );
        }

        return $next($request);
    }
}
