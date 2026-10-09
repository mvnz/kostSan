<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\PaymentReversal;
use App\Models\Pembayaran;
use App\Models\Sewa;
use App\Services\BillingCoverage;
use App\Services\PrivateUpload;
use App\Services\PrivateFileCleanup;
use App\Services\RoomAvailability;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $pembayarans = $this->filteredPayments($request)->latest('periode')->get();

        return view('pembayarans.index', compact('pembayarans'));
    }

    private function filteredPayments(Request $request): Builder
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:lunas,belum_lunas'],
            'metode' => ['nullable', 'in:cash,transfer,e-wallet'],
        ]);
        $query = Pembayaran::with('sewa.kamar', 'sewa.penghuni');
        if (! empty($filters['bulan'])) {
            $period = Carbon::createFromFormat('!Y-m', $filters['bulan']);
            $query->where('periode', '>=', $period->toDateString())
                ->where('periode', '<', $period->copy()->addMonth()->toDateString());
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['metode'])) {
            $query->where('metode', $filters['metode']);
        }
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            // Search literal text: SQL wildcard characters must not broaden results.
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->whereHas('sewa', function (Builder $leases) use ($pattern): void {
                $leases->where(function (Builder $matches) use ($pattern): void {
                    $matches->whereHas('penghuni', fn (Builder $residents) => $residents->whereRaw("nama LIKE ? ESCAPE '!'", [$pattern]))
                        ->orWhereHas('kamar', fn (Builder $rooms) => $rooms->whereRaw("nomor LIKE ? ESCAPE '!'", [$pattern]));
                });
            });
        }

        return $query;
    }

    public function export(Request $request)
    {
        $query = $this->filteredPayments($request);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Kamar', 'Penghuni', 'Periode', 'Tanggal Bayar', 'Metode', 'Jumlah', 'Status'], ',', '"', '');
            $query->chunkById(200, function ($payments) use ($output): void {
                foreach ($payments as $payment) {
                    $row = [
                        $payment->id,
                        $payment->sewa?->kamar?->nomor ?? '-',
                        $payment->sewa?->penghuni?->nama ?? '-',
                        $payment->periode?->toDateString() ?? '',
                        $payment->tanggal_bayar?->toDateString() ?? '',
                        $payment->metode,
                        $payment->jumlah,
                        $payment->status,
                    ];
                    // Prevent spreadsheet software from evaluating user input as formulas.
                    $row = array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : $value, $row);
                    fputcsv($output, $row, ',', '"', '');
                }
            });
            fclose($output);
        }, 'pembayaran-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function create()
    {
        return view('pembayarans.form', [
            'pembayaran' => new Pembayaran,
            'sewas' => Sewa::with('kamar', 'penghuni')->orderByDesc('tanggal_masuk')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sewa_id' => ['required', 'exists:sewas,id'],
            'periode' => ['required', 'date'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode' => ['required', 'in:cash,transfer,e-wallet'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'overlap_override' => ['nullable', 'boolean'],
            'overlap_override_reason' => ['nullable', 'required_if:overlap_override,1', 'string', 'min:10', 'max:500'],
        ]);

        // Pembayaran baru selalu menunggu approval pemilik.
        $validated['status'] = 'belum_lunas';
        $override = (bool) ($validated['overlap_override'] ?? false);
        $overrideReason = $validated['overlap_override_reason'] ?? null;
        unset($validated['overlap_override'], $validated['overlap_override_reason'], $validated['bukti_pembayaran']);
        $storedProof = null;

        try {
            DB::transaction(function () use ($request, $validated, $override, $overrideReason, &$storedProof): void {
                $lease = Sewa::whereKey($validated['sewa_id'])->lockForUpdate()->firstOrFail();
                $start = Carbon::parse($validated['periode'])->startOfMonth();
                $end = $start->copy()->addMonth();
                $overlaps = app(BillingCoverage::class)->overlaps($lease, $start, $end);
                $this->assertManualCoverageAllowed($overlaps, $override);

                $attributes = $validated + [
                    'coverage_start' => $start->toDateString(),
                    'coverage_end' => $end->toDateString(),
                    'overlap_override_reason' => $overlaps ? $overrideReason : null,
                ];
                if ($request->hasFile('bukti_pembayaran')) {
                    $storedProof = app(PrivateUpload::class)->store($request->file('bukti_pembayaran'), 'bukti-pembayaran-sewa', 'bukti_pembayaran');
                    $attributes['bukti_pembayaran_path'] = $storedProof;
                }
                Pembayaran::create($attributes);
            });
        } catch (\Throwable $exception) {
            if ($storedProof) {
                app(PrivateFileCleanup::class)->deleteOrQueue($storedProof, 'payment create rollback');
            }
            throw $exception;
        }

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil ditambahkan dan menunggu approval pemilik.');
    }

    public function show(Pembayaran $pembayaran)
    {
        $pembayaran->load('sewa.kamar', 'sewa.penghuni', 'ledgerEntry', 'reversal.reversalEntry', 'linkAudits.user', 'linkAudits.ledgerEntry');

        return response()->view('pembayarans.show', compact('pembayaran'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function edit(Pembayaran $pembayaran)
    {
        return view('pembayarans.form', [
            'pembayaran' => $pembayaran,
            'sewas' => Sewa::with('kamar', 'penghuni')->orderByDesc('tanggal_masuk')->get(),
        ]);
    }

    public function update(Request $request, Pembayaran $pembayaran)
    {
        $validated = $request->validate([
            'sewa_id' => ['required', 'exists:sewas,id'],
            'periode' => ['required', 'date'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode' => ['required', 'in:cash,transfer,e-wallet'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'overlap_override' => ['nullable', 'boolean'],
            'overlap_override_reason' => ['nullable', 'required_if:overlap_override,1', 'string', 'min:10', 'max:500'],
        ]);

        $override = (bool) ($validated['overlap_override'] ?? false);
        $overrideReason = $validated['overlap_override_reason'] ?? null;
        unset($validated['overlap_override'], $validated['overlap_override_reason'], $validated['bukti_pembayaran']);
        $replacement = null;
        $committed = false;
        try {
            DB::transaction(function () use ($request, $pembayaran, $validated, $override, $overrideReason, &$replacement, &$committed): void {
                $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
                // Keep payment → lease lock order aligned with approval to avoid a lock cycle.
                $lease = Sewa::whereKey($validated['sewa_id'])->lockForUpdate()->firstOrFail();
                $this->assertPaymentEditable($payment);
                $moved = (int) $validated['sewa_id'] !== $payment->sewa_id || $validated['periode'] !== $payment->periode->toDateString();
                if ($payment->coverage_start && $moved) {
                    throw ValidationException::withMessages(['periode' => 'Tagihan dengan cakupan masa sewa tidak dapat dipindahkan ke sewa/periode lain. Hapus tagihan pending lalu buat ulang agar cakupan tetap benar.']);
                }
                if ($moved) {
                    $start = Carbon::parse($validated['periode'])->startOfMonth();
                    $end = $start->copy()->addMonth();
                    $overlaps = app(BillingCoverage::class)->overlaps($lease, $start, $end, $payment->id);
                    $this->assertManualCoverageAllowed($overlaps, $override);
                    $validated['coverage_start'] = $start->toDateString();
                    $validated['coverage_end'] = $end->toDateString();
                    $validated['overlap_override_reason'] = $overlaps ? $overrideReason : null;
                } elseif ($override) {
                    throw ValidationException::withMessages(['overlap_override' => 'Pengecualian hanya digunakan saat membuat atau memindahkan tagihan ke periode yang sudah ditagih.']);
                }
                $validated['status'] = 'belum_lunas';

                if ($request->hasFile('bukti_pembayaran')) {
                    $replacement = app(PrivateUpload::class)->store($request->file('bukti_pembayaran'), 'bukti-pembayaran-sewa', 'bukti_pembayaran');
                    $validated['bukti_pembayaran_path'] = $replacement;
                    $oldProof = $payment->bukti_pembayaran_path;
                    if ($oldProof) {
                        DB::afterCommit(function () use ($oldProof, &$committed): void {
                            $committed = true;
                            app(PrivateFileCleanup::class)->deleteOrQueue($oldProof, 'payment proof replacement');
                        });
                    }
                }

                $payment->update($validated);
            });
        } catch (\Throwable $exception) {
            if ($replacement && ! $committed) {
                // A failed transaction must retain the previously committed proof.
                app(PrivateFileCleanup::class)->deleteOrQueue($replacement, 'payment update rollback');
            }
            throw $exception;
        }

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function destroy(Pembayaran $pembayaran)
    {
        DB::transaction(function () use ($pembayaran): void {
            $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            $this->assertPaymentEditable($payment);
            $oldProof = $payment->bukti_pembayaran_path;
            $payment->delete();
            if ($oldProof) {
                DB::afterCommit(fn () => app(PrivateFileCleanup::class)->deleteOrQueue($oldProof, 'payment delete'));
            }
        });

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil dihapus.');
    }

    private function assertPaymentEditable(Pembayaran $payment): void
    {
        if ($payment->status === 'lunas') {
            throw ValidationException::withMessages([
                'pembayaran' => 'Pembayaran yang sudah disetujui tidak dapat diubah atau dihapus. Catat koreksi secara terpisah untuk menjaga riwayat.',
            ]);
        }
    }

    private function assertManualCoverageAllowed(bool $overlaps, bool $override): void
    {
        if ($overlaps && ! $override) {
            throw ValidationException::withMessages([
                'periode' => 'Periode ini sudah memiliki tagihan. Aktifkan pengecualian hanya untuk cicilan atau tagihan tambahan yang memang disengaja.',
            ]);
        }
        if (! $overlaps && $override) {
            throw ValidationException::withMessages([
                'overlap_override' => 'Tidak ada benturan tagihan pada periode ini; pengecualian tidak diperlukan.',
            ]);
        }
    }

    public function approve(Pembayaran $pembayaran)
    {
        $approved = DB::transaction(function () use ($pembayaran): bool {
            $kamar = Kamar::whereKey($pembayaran->sewa->kamar_id)->lockForUpdate()->firstOrFail();
            $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'lunas') {
                return false;
            }
            $sewa = Sewa::whereKey($payment->sewa_id)->lockForUpdate()->firstOrFail();
            if ($sewa->status !== 'selesai') {
                app(RoomAvailability::class)->assertAvailable($kamar, $sewa->tanggal_masuk->toDateString(), $sewa->tanggal_keluar?->toDateString(), exceptLease: $sewa->id, resident: $sewa->penghuni_id);
                $sewa->update(['status' => 'aktif']);
                $kamar->update(['status' => 'terisi']);
            }
            $payment->update([
                'status' => 'lunas',
                'tanggal_bayar' => $payment->tanggal_bayar ?? now()->toDateString(),
                'keterangan' => trim(($payment->keterangan ?? '').' [Approved admin]'),
            ]);
            $payment->loadMissing('sewa.kamar', 'sewa.penghuni');
            Keuangan::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'tanggal' => $payment->tanggal_bayar->toDateString(),
                    'jenis' => 'pemasukan',
                    'kategori' => 'Sewa Kamar',
                    'deskripsi' => sprintf(
                        'Pembayaran #%d · %s · Kamar %s · Periode %s',
                        $payment->id,
                        $payment->sewa?->penghuni?->nama ?? '-',
                        $payment->sewa?->kamar?->nomor ?? '-',
                        $payment->periode?->format('m/Y') ?? '-'
                    ),
                    'jumlah' => $payment->jumlah,
                ]
            );

            return true;
        });

        if ($approved) {
            $pembayaran->refresh()->load('sewa.penghuni');
            if (filled($pembayaran->sewa?->penghuni?->telepon)) {
                $jumlah = number_format((float) $pembayaran->jumlah, 0, ',', '.');
                app(WhatsAppService::class)->sendByType(
                    'pembayaran_dicatat',
                    (string) $pembayaran->sewa->penghuni->telepon,
                    [
                        'nama' => (string) ($pembayaran->sewa->penghuni->nama ?? 'Penghuni'),
                        'periode' => (string) ($pembayaran->periode?->format('m-Y') ?? '-'),
                        'jumlah' => $jumlah,
                        'status' => (string) ($pembayaran->status ?? '-'),
                    ],
                    "Halo {$pembayaran->sewa->penghuni->nama}, pembayaran kos periode {$pembayaran->periode?->format('m-Y')} sebesar Rp {$jumlah} telah disetujui pemilik dan dicatat dengan status {$pembayaran->status}."
                );
            }
        }

        return back()->with('success', $approved ? 'Pembayaran disetujui dan pemasukan tercatat di buku Keuangan. Sewa yang sudah selesai tetap selesai.' : 'Pembayaran ini sudah disetujui sebelumnya.');
    }

    public function reverse(Request $request, Pembayaran $pembayaran)
    {
        $validated = $request->validate([
            'reversal_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $created = DB::transaction(function () use ($pembayaran, $validated, $request): bool {
            $payment = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            if ($payment->status !== 'lunas') {
                throw ValidationException::withMessages(['pembayaran' => 'Hanya pembayaran yang sudah disetujui dapat dibalik.']);
            }
            if (PaymentReversal::where('payment_id', $payment->id)->exists()) {
                return false;
            }
            $original = Keuangan::where('payment_id', $payment->id)->lockForUpdate()->first();
            if (! $original) {
                throw ValidationException::withMessages(['pembayaran' => 'Pemasukan asal tidak ditemukan. Selesaikan rekonsiliasi sebelum membuat pembalikan.']);
            }
            $ledgerMatches = $payment->tanggal_bayar
                && $original->jenis === 'pemasukan'
                && $original->kategori === 'Sewa Kamar'
                && (string) $original->jumlah === (string) $payment->jumlah
                && $original->tanggal?->equalTo($payment->tanggal_bayar);
            if (! $ledgerMatches) {
                throw ValidationException::withMessages(['pembayaran' => 'Pemasukan asal tidak konsisten dengan pembayaran. Selesaikan rekonsiliasi sebelum membuat pembalikan.']);
            }
            $reversalDate = Carbon::parse($validated['reversal_date'])->startOfDay();
            if ($reversalDate->lessThan($payment->tanggal_bayar->startOfDay())) {
                throw ValidationException::withMessages(['reversal_date' => 'Tanggal pembalikan tidak boleh sebelum tanggal pembayaran.']);
            }
            $reason = trim($validated['reason']);
            $reversalEntry = Keuangan::create([
                'tanggal' => $reversalDate->toDateString(),
                'jenis' => 'pengeluaran',
                'kategori' => 'Pembalikan Pembayaran',
                'deskripsi' => sprintf('Pembalikan pembayaran #%d · %s', $payment->id, mb_strimwidth($reason, 0, 180, '…')),
                'jumlah' => $original->jumlah,
            ]);
            PaymentReversal::create([
                'payment_id' => $payment->id,
                'original_entry_id' => $original->id,
                'reversal_entry_id' => $reversalEntry->id,
                'reversed_by' => $request->user()->id,
                'reversal_date' => $reversalDate->toDateString(),
                'reason' => $reason,
            ]);

            return true;
        });

        return back()->with('success', $created ? 'Pembalikan penuh dicatat sebagai pengeluaran tanpa menghapus pembayaran, invoice, atau pemasukan asal.' : 'Pembayaran ini sudah memiliki pembalikan. Tidak ada transaksi tambahan.');
    }

    private function billingPeriod(Request $request): Carbon
    {
        $validated = $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);

        return Carbon::createFromFormat('!Y-m', $validated['bulan'] ?? now()->format('Y-m'));
    }

    private function billableLeases(Carbon $periode): Builder
    {
        return Sewa::query()->where('status', 'aktif')
            ->whereDate('tanggal_masuk', '<', $periode->copy()->addMonth()->toDateString())
            ->where(fn (Builder $query) => $query->whereNull('tanggal_keluar')
                ->orWhereDate('tanggal_keluar', '>', $periode->toDateString()));
    }

    private function hasMonthlyBill(Sewa $sewa, Carbon $periode): bool
    {
        return app(BillingCoverage::class)->overlaps($sewa, $periode, $periode->copy()->addMonth());
    }

    public function bulkBilling(Request $request)
    {
        $periode = $this->billingPeriod($request);
        $bulan = $periode->format('Y-m');
        $sewas = $this->billableLeases($periode)->with('kamar', 'penghuni')->orderBy('id')->get()
            ->map(fn (Sewa $sewa): array => [
                'sewa' => $sewa,
                'sudah_ada' => $this->hasMonthlyBill($sewa, $periode),
            ]);

        return view('pembayarans.bulk-billing', compact('sewas', 'bulan', 'periode'));
    }

    public function storeBulkBilling(Request $request)
    {
        $periode = $this->billingPeriod($request);
        $selection = $request->validate([
            'pilih_sewa' => ['sometimes', 'boolean'],
            'sewa_ids' => ['required_if:pilih_sewa,1', 'array', 'min:1', 'max:1000'],
            'sewa_ids.*' => ['required', 'integer', 'distinct', 'exists:sewas,id'],
        ]);
        $ids = $selection['sewa_ids'] ?? null;

        [$dibuat, $sudahAda] = DB::transaction(function () use ($periode, $ids): array {
            // Lock the lease before checking/creating bills so repeated bulk requests serialize.
            $query = $this->billableLeases($periode)->orderBy('id');
            if ($ids !== null) {
                $query->whereIn('id', $ids);
            }
            $sewas = $query->lockForUpdate()->get();
            if ($ids !== null && $sewas->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'sewa_ids' => 'Pilihan sewa berubah atau tidak berlaku pada bulan ini. Muat ulang daftar sebelum membuat tagihan.',
                ]);
            }

            $dibuat = 0;
            $sudahAda = 0;
            foreach ($sewas as $sewa) {
                if ($this->hasMonthlyBill($sewa, $periode)) {
                    $sudahAda++;

                    continue;
                }
                Pembayaran::create([
                    'sewa_id' => $sewa->id,
                    'periode' => $periode->toDateString(),
                    'jumlah' => $sewa->biaya_bulanan,
                    'metode' => 'cash',
                    'status' => 'belum_lunas',
                    'keterangan' => 'Tagihan bulk '.$periode->translatedFormat('F Y'),
                ]);
                $dibuat++;
            }

            return [$dibuat, $sudahAda];
        });

        $msg = "{$dibuat} tagihan berhasil dibuat untuk periode {$periode->translatedFormat('F Y')}.";
        if ($sudahAda > 0) {
            $msg .= " {$sudahAda} sewa sudah memiliki tagihan.";
        }

        return redirect()->route('pembayarans.index')->with('success', $msg);
    }
}
