<?php

// CLI-only synthetic fixture. Never accept an arbitrary database/connection.
$root = dirname(__DIR__, 2);
$database = $root.'/storage/framework/testing/browser.sqlite';
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing'
    || getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== $database
    || (getenv('DB_URL') !== false && getenv('DB_URL') !== '')
    || ! is_file($database) || is_link($database) || realpath($database) !== $database
    || strlen((string) getenv('BROWSER_TEST_PASSWORD')) < 12) {
    fwrite(STDERR, "Refusing unsafe browser fixture environment.\n");
    exit(1);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite'
    || $app->configurationIsCached() || filled(config('database.connections.sqlite.url'))
    || config('database.connections.sqlite.database') !== $database
    || App\Models\User::exists() || App\Models\Kamar::exists() || App\Models\Penghuni::exists()) {
    fwrite(STDERR, "Refusing non-empty or misconfigured browser fixture database.\n");
    exit(1);
}
Illuminate\Support\Facades\Http::preventStrayRequests();
Illuminate\Support\Facades\DB::transaction(function (): void {
    $permissions = array_fill_keys([
        'dashboard', 'master_data.data_kamar', 'master_data.data_penghuni',
        'manajemen_sewa.data_sewa', 'manajemen_sewa.sewa_kamar',
        'keuangan.invoice', 'keuangan.data_keuangan',
    ], ['view']);
    $viewer = App\Models\Role::create(['name' => 'Synthetic browser viewer', 'menu_permissions' => $permissions]);
    foreach (['admin' => null, 'viewer' => $viewer->id] as $kind => $roleId) {
        App\Models\User::create([
            'name' => 'Synthetic browser '.$kind, 'email' => $kind.'@browser.example.test',
            'role_id' => $roleId, 'password' => getenv('BROWSER_TEST_PASSWORD'),
        ]);
    }
    App\Models\KamarTipeHarga::create([
        'tipe' => 'Browser', 'harga_1_bulan' => 550000, 'harga_3_bulan' => 550000,
        'harga_6_bulan' => 550000, 'harga_12_bulan' => 550000,
    ]);
    $room = App\Models\Kamar::create(['nomor' => 'BROWSER-01', 'tipe' => 'Browser', 'harga_bulanan' => 550000, 'status' => 'terisi']);
    App\Models\Kamar::create(['nomor' => 'BROWSER-02', 'tipe' => 'Browser', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
    $resident = App\Models\Penghuni::create(['nama' => 'Penghuni Sintetis Browser', 'telepon' => '']);
    $lease = App\Models\Sewa::create([
        'kamar_id' => $room->id, 'penghuni_id' => $resident->id,
        'tanggal_masuk' => now()->startOfMonth(), 'tanggal_keluar' => now()->startOfMonth()->addMonth(),
        'biaya_bulanan' => 550000, 'status' => 'aktif',
    ]);
    App\Models\Reservasi::create([
        'kamar_id' => $room->id, 'penghuni_id' => $resident->id,
        'tanggal_reservasi' => now()->toDateString(), 'rencana_masuk' => now()->addMonths(2)->toDateString(),
        'status' => 'menunggu',
    ]);
    App\Models\Pembayaran::create([
        'sewa_id' => $lease->id, 'periode' => now()->startOfMonth(),
        'jumlah' => 550000, 'metode' => 'cash', 'status' => 'belum_lunas',
    ]);
    $reversed = App\Models\Pembayaran::create([
        'sewa_id' => $lease->id, 'periode' => now()->startOfMonth(), 'tanggal_bayar' => now()->toDateString(),
        'jumlah' => 550000.25, 'metode' => 'cash', 'status' => 'lunas',
    ]);
    $income = App\Models\Keuangan::create([
        'payment_id' => $reversed->id, 'manually_linked_at' => now(),
        'tanggal' => now()->toDateString(), 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar',
        'deskripsi' => 'Synthetic reversed manual income', 'jumlah' => 550000.25,
    ]);
    $offset = App\Models\Keuangan::create([
        'tanggal' => now()->toDateString(), 'jenis' => 'pengeluaran', 'kategori' => 'Pembalikan Pembayaran',
        'deskripsi' => 'Synthetic reversal offset', 'jumlah' => 550000.25,
    ]);
    App\Models\PaymentReversal::create([
        'payment_id' => $reversed->id, 'original_entry_id' => $income->id, 'reversal_entry_id' => $offset->id,
        'reversal_date' => now()->toDateString(), 'reason' => 'Synthetic browser reversal',
    ]);
    foreach (['phone', 'tablet', 'desktop'] as $viewport) {
        $publicRoom = App\Models\Kamar::create(['nomor' => 'BROWSER-PUBLIC-'.$viewport, 'tipe' => 'Browser', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $publicLease = App\Models\Sewa::create(['kamar_id' => $publicRoom->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2027-01-01', 'biaya_bulanan' => '5000000000.00', 'status' => 'aktif']);
        // Known synthetic tokens only inside this guarded, ephemeral browser fixture.
        App\Models\SewaPaymentLink::create(['sewa_id' => $publicLease->id,
            'token' => str_pad('synthetic-public-total-'.$viewport, 64, '0'), 'expires_at' => now()->addDay()]);
    }
});
fwrite(STDOUT, "Synthetic browser fixture ready; no notifications sent.\n");
