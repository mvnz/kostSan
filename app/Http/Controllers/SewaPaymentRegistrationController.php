<?php

namespace App\Http\Controllers;

use App\Models\KostProfile;
use App\Models\Pembayaran;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Services\BillingCoverage;
use App\Services\PrivateUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SewaPaymentRegistrationController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'sewa_id' => ['required', 'exists:sewas,id'],
        ]);

        $link = SewaPaymentLink::create([
            'sewa_id' => $validated['sewa_id'],
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        return redirect()
            ->route('sewas.index')
            ->with('success', 'Link pembayaran sewa berhasil dibuat.')
            ->with('sewa_payment_link', route('sewa-payment-registrations.show', $link->token));
    }

    public function show(string $token)
    {
        $link = SewaPaymentLink::with('sewa.kamar', 'sewa.penghuni')->where('token', $token)->first();

        if (! $link || $link->isExpired()) {
            return view('sewa-payment-registrations.expired');
        }

        $billing = $this->calculateBilling($link->sewa);

        return view('sewa-payment-registrations.form', [
            'link' => $link,
            'billing' => $billing,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $validated = $request->validate([
            'metode' => ['required', 'in:cash,transfer'],
            'keterangan' => ['nullable', 'string'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $uploads = [];
        $committed = false;
        try {
            $created = DB::transaction(function () use ($token, $validated, $request, &$uploads, &$committed) {
                $link = SewaPaymentLink::where('token', $token)->lockForUpdate()->first();

                if (! $link || $link->isExpired()) {
                    return false;
                }

                $lease = Sewa::whereKey($link->sewa_id)->lockForUpdate()->firstOrFail();
                $link->setRelation('sewa', $lease);
                $lease->loadMissing('penghuni');
                $billing = $this->calculateBilling($lease);
                $start = $lease->tanggal_masuk->copy();
                $end = $lease->tanggal_keluar?->copy() ?? $start->copy()->addMonthsNoOverflow($billing['durasi_bulan']);
                if ($end->lessThanOrEqualTo($start)) {
                    throw ValidationException::withMessages(['metode' => 'Masa sewa tidak valid. Hubungi pengelola sebelum membuat pembayaran.']);
                }
                if (app(BillingCoverage::class)->overlaps($lease, $start, $end)) {
                    throw ValidationException::withMessages(['metode' => 'Sudah ada tagihan yang mencakup masa sewa ini. Hubungi pengelola untuk menggunakan tagihan yang ada; pembayaran baru tidak dibuat.']);
                }
                $validated['coverage_start'] = $start->toDateString();
                $validated['coverage_end'] = $end->toDateString();

                $validated['sewa_id'] = $link->sewa_id;
                $validated['periode'] = now()->toDateString();
                $validated['tanggal_bayar'] = now()->toDateString();
                $validated['jumlah'] = $billing['total'];
                $validated['status'] = 'belum_lunas';
                $validated['keterangan'] = trim(($validated['keterangan'] ?? '').' [Menunggu approval admin]');

                if ($request->hasFile('bukti_pembayaran')) {
                    $validated['bukti_pembayaran_path'] = app(PrivateUpload::class)->store($request->file('bukti_pembayaran'), 'bukti-pembayaran-sewa', 'bukti_pembayaran');
                    $uploads[] = $validated['bukti_pembayaran_path'];
                }

                Pembayaran::create($validated);

                $link->update(['used_at' => now()]);
                DB::afterCommit(function () use (&$committed): void {
                    $committed = true;
                });

                return true;
            });
        } catch (\Throwable $exception) {
            if (! $committed) {
                foreach ($uploads as $path) {
                    if ($path) {
                        Storage::disk('local')->delete($path);
                    }
                }
            }
            throw $exception;
        }

        if (! $created) {
            return view('sewa-payment-registrations.expired');
        }

        return view('sewa-payment-registrations.success');
    }

    private function calculateBilling(Sewa $sewa): array
    {
        $biayaBulanan = (float) ($sewa->biaya_bulanan ?? 0);
        $durasiBulan = $sewa->billingMonths();

        $profile = KostProfile::query()->first();

        $hargaTierBulanan = match (true) {
            $durasiBulan <= 2 => (float) ($profile?->diskon_sewa_1_bulan ?? 0),
            $durasiBulan <= 5 => (float) ($profile?->diskon_sewa_3_bulan ?? 0),
            $durasiBulan <= 11 => (float) ($profile?->diskon_sewa_6_bulan ?? 0),
            default => (float) ($profile?->diskon_sewa_12_bulan ?? 0),
        };

        $hargaTierBulanan = $hargaTierBulanan > 0 ? $hargaTierBulanan : $biayaBulanan;
        $subtotalHargaDasar = $biayaBulanan * $durasiBulan;
        $subtotal = $hargaTierBulanan * $durasiBulan;

        $totalSebelumPembulatan = max(0, $subtotal);
        $kelipatanPembulatan = 50000;
        $totalSetelahPembulatan = $this->roundToNearest($totalSebelumPembulatan, $kelipatanPembulatan);
        $totalSetelahPembulatan = max(0, $totalSetelahPembulatan);
        $hematDariHargaDasar = max(0, $subtotalHargaDasar - $totalSetelahPembulatan);

        return [
            'biaya_bulanan' => $biayaBulanan,
            'harga_tier_bulanan' => $hargaTierBulanan,
            'durasi_bulan' => $durasiBulan,
            'subtotal_harga_dasar' => $subtotalHargaDasar,
            'subtotal' => $subtotal,
            'diskon_persen' => 0,
            'diskon' => $hematDariHargaDasar,
            'total_sebelum_pembulatan' => $totalSebelumPembulatan,
            'total' => $totalSetelahPembulatan,
            'pembulatan_kelipatan' => $kelipatanPembulatan,
        ];
    }

    private function roundToNearest(float $amount, int $step): float
    {
        if ($step <= 0) {
            return round($amount);
        }

        return round($amount / $step) * $step;
    }
}
