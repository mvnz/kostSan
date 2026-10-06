<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\KostProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->string('bulan')->toString();
        if ($bulan === '') {
            $bulan = Carbon::now()->format('Y-m');
        }

        $query = Keuangan::query();
        if ($bulan !== '') {
            $periode = Carbon::createFromFormat('Y-m', $bulan);
            $query->whereYear('tanggal', $periode->year)
                ->whereMonth('tanggal', $periode->month);
        }

        $items = (clone $query)->orderByDesc('tanggal')->get();
        $totalPemasukan = (clone $query)->where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = (clone $query)->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldo = $totalPemasukan - $totalPengeluaran;

        return view('laporan-keuangan.index', compact('items', 'totalPemasukan', 'totalPengeluaran', 'saldo', 'bulan'));
    }

    public function pdf(Request $request)
    {
        $bulan = $request->string('bulan')->toString();
        if ($bulan === '') {
            $bulan = Carbon::now()->format('Y-m');
        }

        $periode = Carbon::createFromFormat('Y-m', $bulan);
        $query = Keuangan::query()
            ->whereYear('tanggal', $periode->year)
            ->whereMonth('tanggal', $periode->month);

        $items = (clone $query)->orderBy('tanggal')->get();
        $totalPemasukan = (clone $query)->where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = (clone $query)->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldo = $totalPemasukan - $totalPengeluaran;
        $profile = KostProfile::first();
        $periodeLabel = $periode->translatedFormat('F Y');

        $pdf = Pdf::loadView('laporan-keuangan.pdf', compact(
            'items', 'totalPemasukan', 'totalPengeluaran', 'saldo', 'periodeLabel', 'profile'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("laporan-keuangan-{$bulan}.pdf");
    }
}
