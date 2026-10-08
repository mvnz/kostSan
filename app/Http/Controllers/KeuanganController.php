<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KeuanganController extends Controller
{
    public function index()
    {
        $keuangans = Keuangan::with('payment')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $totalPemasukan = (float) $keuangans->where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = (float) $keuangans->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $bulanIni = Carbon::now()->format('Y-m');
        $pemasukanBulanIni = (float) $keuangans
            ->filter(fn ($item) => $item->jenis === 'pemasukan' && optional($item->tanggal)->format('Y-m') === $bulanIni)
            ->sum('jumlah');
        $pengeluaranBulanIni = (float) $keuangans
            ->filter(fn ($item) => $item->jenis === 'pengeluaran' && optional($item->tanggal)->format('Y-m') === $bulanIni)
            ->sum('jumlah');

        return view('keuangans.index', compact(
            'keuangans',
            'totalPemasukan',
            'totalPengeluaran',
            'saldo',
            'pemasukanBulanIni',
            'pengeluaranBulanIni'
        ));
    }

    public function create()
    {
        return view('keuangans.form', [
            'keuangan' => new Keuangan,
            'kategoriPresets' => $this->kategoriPresets(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:pemasukan,pengeluaran'],
            'kategori' => ['required', 'string', 'max:100'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        if ($request->hasFile('bukti')) {
            $validated['bukti_path'] = $request->file('bukti')->store('bukti-keuangan', 'local');
        }

        Keuangan::create($validated);

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
    }

    public function show(Keuangan $keuangan)
    {
        return redirect()->route('keuangans.edit', $keuangan);
    }

    public function edit(Keuangan $keuangan)
    {
        if ($keuangan->payment_id !== null) {
            return redirect()->route('keuangans.index')->withErrors([
                'keuangan' => 'Pemasukan otomatis mengikuti pembayaran asal dan tidak dapat diubah dari buku Keuangan.',
            ]);
        }

        $this->assertManuallyManaged($keuangan);

        return view('keuangans.form', [
            'keuangan' => $keuangan,
            'kategoriPresets' => $this->kategoriPresets(),
        ]);
    }

    public function update(Request $request, Keuangan $keuangan)
    {
        $this->assertManuallyManaged($keuangan);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:pemasukan,pengeluaran'],
            'kategori' => ['required', 'string', 'max:100'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'hapus_bukti' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('hapus_bukti') && $keuangan->bukti_path) {
            Storage::disk('local')->delete($keuangan->bukti_path);
            $validated['bukti_path'] = null;
        }

        if ($request->hasFile('bukti')) {
            if ($keuangan->bukti_path) {
                Storage::disk('local')->delete($keuangan->bukti_path);
            }

            $validated['bukti_path'] = $request->file('bukti')->store('bukti-keuangan', 'local');
        }

        $keuangan->update($validated);

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil diperbarui.');
    }

    public function destroy(Keuangan $keuangan)
    {
        $this->assertManuallyManaged($keuangan);

        if ($keuangan->bukti_path) {
            Storage::disk('local')->delete($keuangan->bukti_path);
        }

        $keuangan->delete();

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil dihapus.');
    }

    private function kategoriPresets(): array
    {
        return [
            'pemasukan' => [
                'Sewa Kamar',
                'Denda Keterlambatan',
                'Uang Jaminan',
                'Lain-lain Pemasukan',
            ],
            'pengeluaran' => [
                'Listrik',
                'Air',
                'Internet',
                'Perawatan',
                'Kebersihan',
                'Gaji',
                'Lain-lain Pengeluaran',
            ],
        ];
    }

    private function assertManuallyManaged(Keuangan $keuangan): void
    {
        if ($keuangan->payment_id !== null) {
            throw ValidationException::withMessages([
                'keuangan' => 'Pemasukan otomatis mengikuti pembayaran asal dan tidak dapat diubah atau dihapus dari buku Keuangan.',
            ]);
        }
    }
}
