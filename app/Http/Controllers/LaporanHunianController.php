<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Sewa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanHunianController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['tahun' => ['nullable', 'integer', 'between:1900,9999']]);
        $tahun = (int) ($filters['tahun'] ?? Carbon::now()->year);
        $totalKamar = Kamar::count();

        $bulanData = [];
        for ($m = 1; $m <= 12; $m++) {
            $awal  = Carbon::create($tahun, $m, 1)->startOfMonth();
            $akhir = $awal->copy()->addMonth()->toDateString();

            $terisi = Sewa::whereDate('tanggal_masuk', '<', $akhir)
                ->where(function ($q) use ($awal) {
                    $q->whereNull('tanggal_keluar')
                      ->orWhereDate('tanggal_keluar', '>', $awal->toDateString());
                })
                ->where('status', 'aktif')
                ->distinct()->count('kamar_id');

            $pct = $totalKamar > 0 ? round($terisi / $totalKamar * 100, 1) : 0;

            $bulanData[] = [
                'bulan'       => $awal->translatedFormat('F'),
                'bulan_short' => $awal->translatedFormat('M'),
                'terisi'      => $terisi,
                'kosong'      => max(0, $totalKamar - $terisi),
                'pct'         => $pct,
            ];
        }

        // Per-kamar status saat ini
        $kamars = Kamar::with(['sewas' => function ($q) {
            $q->with('penghuni')->where('status', 'aktif');
        }])->orderBy('nomor')->get();

        $avgHunian = round(collect($bulanData)->avg('pct'), 1);
        $tahunList = range(Carbon::now()->year, max(2024, Carbon::now()->year - 4), -1);

        return view('laporan-hunian.index', compact(
            'bulanData', 'totalKamar', 'tahun', 'tahunList', 'kamars', 'avgHunian'
        ));
    }
}
