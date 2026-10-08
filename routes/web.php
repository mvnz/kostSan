<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\LaporanHunianController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\KamarFloorController;
use App\Http\Controllers\KamarTipeHargaController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PenghuniController;
use App\Http\Controllers\PenghuniRegistrationController;
use App\Http\Controllers\ProfilKostController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\SewaPaymentRegistrationController;
use App\Http\Controllers\UserManagementController;
use App\Models\Keuangan;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

// Guest-only auth routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
});
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Public registration & payment links (no auth needed)
Route::middleware('throttle:public-links')->group(function () {
    Route::get('pendaftaran/penghuni/{token}', [PenghuniRegistrationController::class, 'show'])->name('penghuni-registrations.show');
    Route::post('pendaftaran/penghuni/{token}', [PenghuniRegistrationController::class, 'store'])->name('penghuni-registrations.store');
    Route::get('pembayaran/sewa/{token}', [SewaPaymentRegistrationController::class, 'show'])->name('sewa-payment-registrations.show');
    Route::post('pembayaran/sewa/{token}', [SewaPaymentRegistrationController::class, 'store'])->name('sewa-payment-registrations.store');
});

// Protected routes
Route::middleware(['auth', 'menu.permission'])->group(function () {

Route::get('/', function () {
    $totalKamar = Kamar::count();
    $kamarTersedia = Kamar::where('status', 'tersedia')->count();
    $totalPemasukan = Keuangan::where('jenis', 'pemasukan')->sum('jumlah');
    $totalPengeluaran = Keuangan::where('jenis', 'pengeluaran')->sum('jumlah');

    // 6 bulan tren keuangan
    $labels = [];
    $dataPemasukan = [];
    $dataPengeluaran = [];

    for ($offset = 5; $offset >= 0; $offset--) {
        $periode = Carbon::now()->startOfMonth()->subMonths($offset);
        $labels[] = $periode->translatedFormat('M');
        $dataPemasukan[] = (float) Keuangan::whereYear('tanggal', $periode->year)
            ->whereMonth('tanggal', $periode->month)
            ->where('jenis', 'pemasukan')
            ->sum('jumlah');
        $dataPengeluaran[] = (float) Keuangan::whereYear('tanggal', $periode->year)
            ->whereMonth('tanggal', $periode->month)
            ->where('jenis', 'pengeluaran')
            ->sum('jumlah');
    }

    // 6 bulan tren hunian
    $hunianLabels = [];
    $hunianData = [];
    $hunianPct = [];
    for ($offset = 5; $offset >= 0; $offset--) {
        $bulan = Carbon::now()->startOfMonth()->subMonths($offset);
        $hunianLabels[] = $bulan->translatedFormat('M');
        $terisi = app(\App\Services\MonthlyOccupancy::class)->rooms($bulan, includeCompleted: true);
        $hunianData[] = $terisi;
        $hunianPct[] = $totalKamar > 0 ? round($terisi / $totalKamar * 100) : 0;
    }

    $stat = [
        'total_kamar' => $totalKamar,
        'kamar_terisi' => max($totalKamar - $kamarTersedia, 0),
        'kamar_tersedia' => $kamarTersedia,
        'penghuni_aktif' => Sewa::where('status', 'aktif')->distinct('penghuni_id')->count('penghuni_id'),
        'total_penghuni' => Penghuni::count(),
        'total_reservasi' => Reservasi::count(),
        'tagihan_belum_lunas' => Pembayaran::where('status', 'belum_lunas')->sum('jumlah'),
        'total_pemasukan' => $totalPemasukan,
        'total_pengeluaran' => $totalPengeluaran,
        'saldo_keuangan' => $totalPemasukan - $totalPengeluaran,
    ];

    $chartData = [
        'labels' => $labels,
        'pemasukan' => $dataPemasukan,
        'pengeluaran' => $dataPengeluaran,
    ];

    $hunianChart = [
        'labels' => $hunianLabels,
        'data' => $hunianData,
        'pct' => $hunianPct,
    ];

    // Kamar paling lama kosong
    $kamarKosong = Kamar::where('status', 'tersedia')
        ->with(['sewas' => fn($q) => $q->whereNotNull('tanggal_keluar')->latest('tanggal_keluar')->limit(1)])
        ->get()
        ->map(function ($kamar) {
            $lastSewa = $kamar->sewas->first();
            $sejak = $lastSewa?->tanggal_keluar ? Carbon::parse($lastSewa->tanggal_keluar) : null;
            return [
                'kamar'     => $kamar,
                'sejak'     => $sejak,
                'lama_hari' => $sejak ? now()->diffInDays($sejak) : 9999,
                'label'     => $sejak ? $sejak->format('d M Y') : 'Belum pernah dihuni',
            ];
        })
        ->sortByDesc('lama_hari')
        ->take(8)
        ->values();

    $sewaTerbaru = Sewa::with(['kamar', 'penghuni'])->latest()->take(5)->get();

    $hariIni = Carbon::today();
    $batasAkhir = Carbon::today()->addDays(7);
    $sewaAkanBerakhir = Sewa::with(['kamar', 'penghuni'])
        ->where('status', 'aktif')
        ->whereNotNull('tanggal_keluar')
        ->whereDate('tanggal_keluar', '>=', $hariIni)
        ->whereDate('tanggal_keluar', '<=', $batasAkhir)
        ->orderBy('tanggal_keluar')
        ->get();

    $pembayaranTerbaru = Pembayaran::with('sewa.penghuni', 'sewa.kamar')
        ->latest('periode')->take(5)->get();

    $penghuniTerbaru = Penghuni::latest()->take(5)->get();

    return view('dashboard', compact(
        'stat', 'sewaTerbaru', 'sewaAkanBerakhir',
        'chartData', 'hunianChart', 'kamarKosong',
        'pembayaranTerbaru', 'penghuniTerbaru'
    ));
})->name('dashboard');

Route::get('kamars/sewa', [KamarController::class, 'sewa'])->name('kamars.sewa');
Route::post('kamars/{kamar}/selesai-sewa', [KamarController::class, 'selesaiSewa'])->name('kamars.selesai-sewa');
Route::post('kamars/{kamar}/perpanjang-sewa', [KamarController::class, 'perpanjangSewa'])->name('kamars.perpanjang-sewa');
Route::post('kamars/{kamar}/layout', [KamarController::class, 'updateLayout'])->name('kamars.update-layout');
Route::resource('kamars', KamarController::class);
Route::get('master-data/lantai', [KamarFloorController::class, 'index'])->name('kamar-floors.index');
Route::post('master-data/lantai', [KamarFloorController::class, 'store'])->name('kamar-floors.store');
Route::put('master-data/lantai/{kamarFloor}', [KamarFloorController::class, 'update'])->name('kamar-floors.update');
Route::delete('master-data/lantai/{kamarFloor}', [KamarFloorController::class, 'destroy'])->name('kamar-floors.destroy');
Route::get('pengaturan/tipe-kamar', [KamarTipeHargaController::class, 'index'])->name('kamar-tipe-hargas.index');
Route::post('pengaturan/tipe-kamar', [KamarTipeHargaController::class, 'store'])->name('kamar-tipe-hargas.store');
Route::put('pengaturan/tipe-kamar/{kamarTipeHarga}', [KamarTipeHargaController::class, 'update'])->name('kamar-tipe-hargas.update');
Route::delete('pengaturan/tipe-kamar/{kamarTipeHarga}', [KamarTipeHargaController::class, 'destroy'])->name('kamar-tipe-hargas.destroy');
Route::get('penghunis/cetak', [PenghuniController::class, 'cetak'])->name('penghunis.cetak');
Route::post('penghunis/registration-links', [PenghuniRegistrationController::class, 'generate'])->name('penghuni-registrations.generate');
Route::resource('penghunis', PenghuniController::class);
Route::get('sewas/pilih-kamar', function () {
    return redirect()->route('sewas.create');
})->name('sewas.pilih-kamar');
Route::resource('sewas', SewaController::class);
Route::post('sewas/payment-links', [SewaPaymentRegistrationController::class, 'generate'])->name('sewa-payment-registrations.generate');
Route::get('pembayarans/export', [PembayaranController::class, 'export'])->name('pembayarans.export');
Route::resource('pembayarans', PembayaranController::class);
Route::post('pembayarans/{pembayaran}/approve', [PembayaranController::class, 'approve'])->name('pembayarans.approve');
Route::get('pembayarans-bulk', [PembayaranController::class, 'bulkBilling'])->name('pembayarans.bulk-billing');
Route::post('pembayarans-bulk', [PembayaranController::class, 'storeBulkBilling'])->name('pembayarans.bulk-billing.store');
Route::resource('reservasis', ReservasiController::class);
Route::get('keuangans/export', [KeuanganController::class, 'export'])->name('keuangans.export');
Route::get('keuangans/reconciliation', [KeuanganController::class, 'reconciliation'])->name('keuangans.reconciliation');
Route::resource('keuangans', KeuanganController::class);
Route::get('laporan-keuangan', [LaporanKeuanganController::class, 'index'])->name('laporan-keuangan.index');
Route::get('laporan-keuangan/pdf', [LaporanKeuanganController::class, 'pdf'])->name('laporan-keuangan.pdf');
Route::get('laporan-hunian', [LaporanHunianController::class, 'index'])->name('laporan-hunian.index');

Route::get('log-aktivitas', [ActivityLogController::class, 'index'])->name('activity-logs.index');

Route::get('secure-files/{path}', [SecureFileController::class, 'show'])->where('path', '.*')->name('secure-files.show');

// Kontrak Sewa PDF
Route::get('sewas/{sewa}/kontrak', [SewaController::class, 'kontrak'])->name('sewas.kontrak');
Route::get('invoices/reconciliation', [InvoiceController::class, 'reconciliation'])->name('invoices.reconciliation');
Route::resource('invoices', InvoiceController::class);
Route::post('invoices/refresh-from-payments', [InvoiceController::class, 'refreshFromPayments'])->name('invoices.refresh-from-payments');
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
Route::get('profil-kost', function () {
    return redirect()->route('profil-kost.profile');
})->name('profil-kost.edit');
Route::get('profil-kost/profil', [ProfilKostController::class, 'profile'])->name('profil-kost.profile');
Route::put('profil-kost/profil', [ProfilKostController::class, 'updateProfile'])->name('profil-kost.profile.update');
Route::get('profil-kost/notifikasi', [ProfilKostController::class, 'notification'])->name('profil-kost.notification');
Route::put('profil-kost/notifikasi', [ProfilKostController::class, 'updateNotification'])->name('profil-kost.notification.update');
Route::get('profil-akun', [ProfileController::class, 'show'])->name('profile.show');
Route::put('profil-akun', [ProfileController::class, 'update'])->name('profile.update');
Route::put('profil-akun/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

Route::view('bantuan', 'bantuan.index')->name('bantuan.index');
Route::resource('roles', RoleController::class);
Route::resource('users', UserManagementController::class);

}); // end auth middleware group
