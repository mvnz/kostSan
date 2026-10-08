<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Pembayaran;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function reconciliation(Request $request)
    {
        abort_unless($request->user()->hasMenuPermission('manajemen_sewa.data_sewa', 'view'), 403);
        $filters = $request->validate([
            'kategori' => ['nullable', 'in:tanpa_pemasukan,tidak_sesuai'],
            'bulan' => ['nullable', 'date_format:Y-m'],
        ]);
        $kategori = $filters['kategori'] ?? 'tanpa_pemasukan';
        $bulan = $filters['bulan'] ?? null;
        $missing = Pembayaran::query()->where('status', 'lunas')->whereDoesntHave('ledgerEntry');
        $mismatch = Pembayaran::query()->whereHas('ledgerEntry', function ($entries): void {
            $entries->where(function ($differences): void {
                $differences->whereColumn('keuangans.jumlah', '<>', 'pembayarans.jumlah')
                    ->orWhere('keuangans.jenis', '<>', 'pemasukan')
                    ->orWhere('keuangans.kategori', '<>', 'Sewa Kamar')
                    ->orWhere('pembayarans.status', '<>', 'lunas')
                    ->orWhereNull('pembayarans.tanggal_bayar')
                    ->orWhereColumn('keuangans.tanggal', '<>', 'pembayarans.tanggal_bayar');
            });
        });
        if ($bulan) {
            $start = CarbonImmutable::createFromFormat('!Y-m', $bulan)->startOfMonth();
            $end = $start->addMonth()->toDateString();
            $start = $start->toDateString();
            $paymentMonth = function ($payments) use ($start, $end): void {
                $payments->where(fn ($dated) => $dated->where('tanggal_bayar', '>=', $start)->where('tanggal_bayar', '<', $end))
                    ->orWhere(fn ($undated) => $undated->whereNull('tanggal_bayar')->where('periode', '>=', $start)->where('periode', '<', $end));
            };
            $missing->where($paymentMonth);
            $mismatch->where(function ($affected) use ($paymentMonth, $start, $end): void {
                $affected->where($paymentMonth)->orWhereHas('ledgerEntry', fn ($entries) => $entries->where('tanggal', '>=', $start)->where('tanggal', '<', $end));
            });
        }
        $counts = ['tanpa_pemasukan' => (clone $missing)->count(), 'tidak_sesuai' => (clone $mismatch)->count()];
        $records = ($kategori === 'tidak_sesuai' ? $mismatch : $missing)
            ->with('sewa.penghuni', 'sewa.kamar', 'ledgerEntry')->orderBy('id')->paginate(25)->withQueryString();

        return response()->view('keuangans.reconciliation', compact('kategori', 'bulan', 'counts', 'records'))
            ->header('Cache-Control', 'no-store, private');
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

        try {
            DB::transaction(fn () => Keuangan::create($validated));
        } catch (\Throwable $exception) {
            if (! empty($validated['bukti_path'])) {
                Storage::disk('local')->delete($validated['bukti_path']);
            }
            throw $exception;
        }

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
    }

    public function show(Keuangan $keuangan)
    {
        $keuangan->load('payment');

        return response()->view('keuangans.show', compact('keuangan'))
            ->header('Cache-Control', 'no-store, private');
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

        $replacement = null;
        $committed = false;
        try {
            DB::transaction(function () use ($request, $keuangan, $validated, &$replacement, &$committed): void {
                $entry = Keuangan::whereKey($keuangan->id)->lockForUpdate()->firstOrFail();
                $this->assertManuallyManaged($entry);
                $oldProof = $entry->bukti_path;
                if ($request->boolean('hapus_bukti')) {
                    $validated['bukti_path'] = null;
                }
                if ($request->hasFile('bukti')) {
                    $replacement = $request->file('bukti')->store('bukti-keuangan', 'local');
                    $validated['bukti_path'] = $replacement;
                }
                $entry->update($validated);
                DB::afterCommit(function () use ($oldProof, $validated, &$committed): void {
                    $committed = true;
                    if ($oldProof && array_key_exists('bukti_path', $validated) && $oldProof !== $validated['bukti_path']) {
                        Storage::disk('local')->delete($oldProof);
                    }
                });
            });
        } catch (\Throwable $exception) {
            if ($replacement && ! $committed) {
                Storage::disk('local')->delete($replacement);
            }
            throw $exception;
        }

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil diperbarui.');
    }

    public function destroy(Keuangan $keuangan)
    {
        $this->assertManuallyManaged($keuangan);
        DB::transaction(function () use ($keuangan): void {
            $entry = Keuangan::whereKey($keuangan->id)->lockForUpdate()->firstOrFail();
            $this->assertManuallyManaged($entry);
            $oldProof = $entry->bukti_path;
            $entry->delete();
            if ($oldProof) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($oldProof));
            }
        });

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
