<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Services\MonthlyOccupancy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanHunianController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:1900,9999'],
            'cakupan' => ['nullable', 'in:aktif,riwayat'],
        ]);
        $tahun = (int) ($filters['tahun'] ?? Carbon::now()->year);
        $cakupan = $filters['cakupan'] ?? 'aktif';
        $totalKamar = Kamar::count();

        $bulanData = [];
        for ($m = 1; $m <= 12; $m++) {
            $awal = Carbon::create($tahun, $m, 1)->startOfMonth();
            $terisi = app(MonthlyOccupancy::class)->rooms($awal, $cakupan === 'riwayat');

            $pct = $totalKamar > 0 ? round($terisi / $totalKamar * 100, 1) : 0;

            $bulanData[] = [
                'bulan' => $awal->translatedFormat('F'),
                'bulan_short' => $awal->translatedFormat('M'),
                'terisi' => $terisi,
                'kosong' => max(0, $totalKamar - $terisi),
                'pct' => $pct,
            ];
        }

        // Per-kamar status saat ini
        $kamars = Kamar::with(['sewas' => function ($q) {
            $q->with('penghuni')
                ->whereIn('status', ['aktif', 'menunggak'])
                ->whereDate('tanggal_masuk', '<=', today())
                ->where(fn ($leases) => $leases->whereNull('tanggal_keluar')->orWhereDate('tanggal_keluar', '>', today()));
        }, 'confirmedReservations' => function ($q) {
            $q->with('penghuni')->orderBy('rencana_masuk');
        }])->orderBy('nomor')->get();

        $avgHunian = round(collect($bulanData)->avg('pct'), 1);
        $tahunList = range(Carbon::now()->year, max(2024, Carbon::now()->year - 4), -1);
        $tahunList = collect([...$tahunList, $tahun])->unique()->sortDesc()->values()->all();

        return view('laporan-hunian.index', compact(
            'bulanData', 'totalKamar', 'tahun', 'tahunList', 'kamars', 'avgHunian', 'cakupan'
        ));
    }
}
