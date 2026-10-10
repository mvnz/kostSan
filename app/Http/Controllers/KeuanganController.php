<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\FinancePaymentLinkAudit;
use App\Models\Pembayaran;
use App\Services\PrivateUpload;
use App\Services\PrivateFileCleanup;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeuanganController extends Controller
{
    public function index()
    {
        $keuangans = Keuangan::with('payment', 'reversalSource')
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

    public function export(Request $request)
    {
        $filters = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'jenis' => ['nullable', 'in:pemasukan,pengeluaran'],
        ]);
        $query = Keuangan::with('reversalSource');
        if (! empty($filters['bulan'])) {
            $start = CarbonImmutable::createFromFormat('!Y-m', $filters['bulan']);
            $query->where('tanggal', '>=', $start->toDateString())
                ->where('tanggal', '<', $start->addMonth()->toDateString());
        }
        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Tanggal', 'Jenis', 'Kategori', 'Deskripsi', 'Jumlah', 'Sumber', 'ID Pembayaran'], ',', '"', '');
            $query->chunkById(200, function ($entries) use ($output): void {
                foreach ($entries as $entry) {
                    $source = $entry->manually_linked_at ? 'rekonsiliasi manual' : ($entry->payment_id ? 'otomatis' : ($entry->reversalSource ? 'pembalikan' : 'manual'));
                    $paymentId = $entry->payment_id ?? $entry->reversalSource?->payment_id ?? '';
                    $row = [$entry->id, $entry->tanggal?->toDateString() ?? '', $entry->jenis, $entry->kategori, $entry->deskripsi, $entry->jumlah, $source, $paymentId];
                    $row = array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : $value, $row);
                    fputcsv($output, $row, ',', '"', '');
                }
            });
            fclose($output);
        }, 'keuangan-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
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
        $candidates = collect();
        if ($kategori === 'tanpa_pemasukan') {
            foreach ($records as $payment) {
                $candidates[$payment->id] = $payment->tanggal_bayar
                    ? $this->manualIncomeCandidates($payment)->get()
                    : collect();
            }
        }

        return response()->view('keuangans.reconciliation', compact('kategori', 'bulan', 'counts', 'records', 'candidates'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function attachManualIncome(Request $request, Pembayaran $pembayaran)
    {
        abort_unless($request->user()->hasMenuPermission('manajemen_sewa.data_sewa', 'update'), 403);
        $validated = $request->validate([
            'keuangan_id' => ['required', 'integer', 'exists:keuangans,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'bulan' => ['nullable', 'date_format:Y-m'],
        ]);

        DB::transaction(function () use ($request, $pembayaran, $validated): void {
            $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            $entry = Keuangan::whereKey($validated['keuangan_id'])->lockForUpdate()->firstOrFail();
            if ($payment->status !== 'lunas' || $payment->ledgerEntry()->exists() || ! $this->manualIncomeMatches($payment, $entry)) {
                throw ValidationException::withMessages([
                    'keuangan_id' => 'Pemasukan tidak tersedia atau tidak persis cocok pada tanggal, nominal, jenis, dan kategori pembayaran.',
                ]);
            }

            $entry->update([
                'payment_id' => $payment->id,
                'manually_linked_at' => now(),
                'manually_linked_by' => $request->user()->id,
            ]);
            FinancePaymentLinkAudit::create([
                'payment_id' => $payment->id,
                'ledger_entry_id' => $entry->id,
                'user_id' => $request->user()->id,
                'action' => 'linked',
                'reason' => $validated['reason'],
            ]);
        });

        return redirect()->route('keuangans.reconciliation', array_filter(['bulan' => $validated['bulan'] ?? null]))
            ->with('success', 'Pemasukan manual ditautkan ke pembayaran dan jejak audit disimpan.');
    }

    public function detachManualIncome(Request $request, Pembayaran $pembayaran)
    {
        abort_unless($request->user()->hasMenuPermission('manajemen_sewa.data_sewa', 'update'), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $entryId = DB::transaction(function () use ($request, $pembayaran, $validated): int {
            $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            $entry = Keuangan::where('payment_id', $payment->id)->lockForUpdate()->first();
            if (! $entry || ! $entry->manually_linked_at) {
                throw ValidationException::withMessages([
                    'keuangan' => 'Hanya tautan rekonsiliasi manual yang dapat dilepas. Pemasukan dari approval tetap immutable.',
                ]);
            }

            FinancePaymentLinkAudit::create([
                'payment_id' => $payment->id,
                'ledger_entry_id' => $entry->id,
                'user_id' => $request->user()->id,
                'action' => 'unlinked',
                'reason' => $validated['reason'],
            ]);
            $entry->update(['payment_id' => null, 'manually_linked_at' => null, 'manually_linked_by' => null]);

            return $entry->id;
        });

        return redirect()->route('keuangans.show', $entryId)
            ->with('success', 'Tautan manual dilepas tanpa menghapus pembayaran atau pemasukan; jejak audit dipertahankan.');
    }

    private function manualIncomeCandidates(Pembayaran $payment)
    {
        return Keuangan::query()
            ->whereNull('payment_id')
            ->whereDate('tanggal', $payment->tanggal_bayar->toDateString())
            ->where('jumlah', $payment->jumlah)
            ->where('jenis', 'pemasukan')
            ->where('kategori', 'Sewa Kamar')
            ->whereDoesntHave('reversalSource')
            ->orderBy('id');
    }

    private function manualIncomeMatches(Pembayaran $payment, Keuangan $entry): bool
    {
        return $payment->tanggal_bayar !== null
            && $entry->payment_id === null
            && ! $entry->reversalSource()->exists()
            && $entry->jenis === 'pemasukan'
            && $entry->kategori === 'Sewa Kamar'
            && (string) $entry->jumlah === (string) $payment->jumlah
            && $entry->tanggal?->equalTo($payment->tanggal_bayar);
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
            $validated['bukti_path'] = app(PrivateUpload::class)->store($request->file('bukti'), 'bukti-keuangan', 'bukti');
        }

        try {
            DB::transaction(fn () => Keuangan::create($validated));
        } catch (\Throwable $exception) {
            if (! empty($validated['bukti_path'])) {
                app(PrivateFileCleanup::class)->deleteOrQueue($validated['bukti_path'], 'finance create rollback');
            }
            throw $exception;
        }

        return redirect()->route('keuangans.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
    }

    public function show(Keuangan $keuangan)
    {
        $keuangan->load('payment', 'reversalSource.payment', 'linkAudits.user');

        return response()->view('keuangans.show', compact('keuangan'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function edit(Keuangan $keuangan)
    {
        if ($keuangan->payment_id !== null || $keuangan->reversalSource()->exists()) {
            return redirect()->route('keuangans.index')->withErrors([
                'keuangan' => 'Transaksi otomatis atau pembalikan mengikuti pembayaran asal dan tidak dapat diubah dari buku Keuangan.',
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
                    $replacement = app(PrivateUpload::class)->store($request->file('bukti'), 'bukti-keuangan', 'bukti');
                    $validated['bukti_path'] = $replacement;
                }
                $entry->update($validated);
                DB::afterCommit(function () use ($oldProof, $validated, &$committed): void {
                    $committed = true;
                    if ($oldProof && array_key_exists('bukti_path', $validated) && $oldProof !== $validated['bukti_path']) {
                        app(PrivateFileCleanup::class)->deleteOrQueue($oldProof, 'finance proof replacement');
                    }
                });
            });
        } catch (\Throwable $exception) {
            if ($replacement && ! $committed) {
                app(PrivateFileCleanup::class)->deleteOrQueue($replacement, 'finance update rollback');
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
                DB::afterCommit(fn () => app(PrivateFileCleanup::class)->deleteOrQueue($oldProof, 'finance delete'));
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
        if ($keuangan->payment_id !== null || $keuangan->reversalSource()->exists()) {
            throw ValidationException::withMessages([
                'keuangan' => 'Transaksi otomatis atau pembalikan mengikuti pembayaran asal dan tidak dapat diubah atau dihapus dari buku Keuangan.',
            ]);
        }
    }
}
