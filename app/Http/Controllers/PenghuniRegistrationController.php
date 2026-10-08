<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\PenghuniRegistrationLink;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Services\PrivateUpload;
use App\Services\RoomAvailability;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PenghuniRegistrationController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'kamar_id' => ['nullable', 'exists:kamars,id'],
        ]);

        if (! empty($validated['kamar_id'])) {
            $kamar = Kamar::find($validated['kamar_id']);
            if (! $kamar || $kamar->status !== 'tersedia') {
                return back()->with('error', 'Link pendaftaran hanya bisa dibuat untuk kamar yang tersedia.');
            }
        }

        $link = PenghuniRegistrationLink::create([
            'kamar_id' => $validated['kamar_id'] ?? null,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $redirectRoute = ! empty($validated['kamar_id']) ? 'kamars.sewa' : 'penghunis.index';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Link pendaftaran baru berhasil dibuat.')
            ->with('registration_link', route('penghuni-registrations.show', $link->token));
    }

    public function show(string $token)
    {
        $link = PenghuniRegistrationLink::with('kamar')->where('token', $token)->first();

        if (! $link || $link->isExpired()) {
            return view('penghuni-registrations.expired');
        }

        return view('penghuni-registrations.form', [
            'penghuni' => new Penghuni,
            'token' => $token,
            'link' => $link,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_kamar' => ['nullable', 'string', 'max:30'],
            'jumlah_kunci' => ['nullable', 'integer', 'min:0', 'max:20'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'nik' => ['nullable', 'string', 'max:30'],
            'telepon' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat_ktp' => ['nullable', 'string'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'tanggal_mulai_tinggal' => ['nullable', 'date'],
            'waktu_sewa_bulan' => ['required', 'in:1,3,6,12'],
            'kontak_darurat_nama' => ['nullable', 'string', 'max:150'],
            'kontak_darurat_hubungan' => ['nullable', 'string', 'max:100'],
            'kontak_darurat_telepon' => ['nullable', 'string', 'max:50'],
            'jenis_kendaraan' => ['nullable', 'in:tidak_ada,motor'],
            'kendaraan_merek_tipe' => ['nullable', 'string', 'max:100'],
            'kendaraan_warna' => ['nullable', 'string', 'max:50'],
            'kendaraan_nomor_polisi' => ['nullable', 'string', 'max:30'],
            'foto_ktp' => ['required', 'image', 'max:4096'],
            'foto_selfie' => ['required', 'image', 'max:4096'],
        ]);

        $uploads = [];
        $committed = false;
        try {
            $created = DB::transaction(function () use ($token, $validated, $request, &$uploads, &$committed) {
                $link = PenghuniRegistrationLink::with('kamar')->where('token', $token)->lockForUpdate()->first();

                if (! $link || $link->isExpired()) {
                    return null;
                }

                $kamar = $link->kamar_id ? Kamar::whereKey($link->kamar_id)->lockForUpdate()->firstOrFail() : null;
                if ($kamar === null && ! empty($validated['nomor_kamar'])) {
                    $kamar = Kamar::where('nomor', trim((string) $validated['nomor_kamar']))->lockForUpdate()->first();
                }

                if ($kamar) {
                    $validated['nomor_kamar'] = (string) $kamar->nomor;
                    $validated['harga_sewa'] = (float) $kamar->harga_bulanan;
                }

                $lamaBulan = max(1, (int) ($validated['waktu_sewa_bulan'] ?? 1));
                $tanggalMasukForMeta = ! empty($validated['tanggal_mulai_tinggal'])
                    ? Carbon::parse($validated['tanggal_mulai_tinggal'])
                    : Carbon::today();

                $validated['lama_sewa_bulan'] = $lamaBulan;
                $validated['tanggal_jatuh_tempo'] = (clone $tanggalMasukForMeta)->addMonthsNoOverflow($lamaBulan)->toDateString();

                if ($kamar) {
                    app(RoomAvailability::class)->assertAvailable($kamar, $tanggalMasukForMeta->toDateString(), $validated['tanggal_jatuh_tempo']);
                }

                $validated['foto_ktp_path'] = app(PrivateUpload::class)->store($request->file('foto_ktp'), 'penghuni-dokumen/ktp', 'foto_ktp');
                $uploads[] = $validated['foto_ktp_path'];
                $validated['foto_selfie_path'] = app(PrivateUpload::class)->store($request->file('foto_selfie'), 'penghuni-dokumen/selfie', 'foto_selfie');
                $uploads[] = $validated['foto_selfie_path'];

                unset($validated['waktu_sewa_bulan']);

                $penghuni = Penghuni::create($validated);
                $paymentLinkUrl = null;

                if ($kamar) {
                    $tanggalMasuk = $tanggalMasukForMeta->copy();
                    $tanggalKeluar = (clone $tanggalMasuk)->addMonthsNoOverflow($lamaBulan);

                    $sewa = Sewa::create([
                        'kamar_id' => $kamar->id,
                        'penghuni_id' => $penghuni->id,
                        'tanggal_masuk' => $tanggalMasuk->toDateString(),
                        'tanggal_keluar' => $tanggalKeluar->toDateString(),
                        'biaya_bulanan' => (float) ($validated['harga_sewa'] ?? $kamar->harga_bulanan ?? 0),
                        'uang_jaminan' => 0,
                        'status' => 'menunggak',
                        'catatan' => 'AUTO: dibuat dari link pendaftaran penghuni, menunggu approval pembayaran',
                    ]);

                    $paymentLink = SewaPaymentLink::create([
                        'sewa_id' => $sewa->id,
                        'token' => Str::random(64),
                        'expires_at' => now()->addDays(7),
                    ]);

                    $paymentLinkUrl = route('sewa-payment-registrations.show', $paymentLink->token);
                }

                $link->update(['used_at' => now()]);
                DB::afterCommit(function () use (&$committed): void {
                    $committed = true;
                });

                return [
                    'penghuni' => $penghuni,
                    'payment_link_url' => $paymentLinkUrl,
                ];
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
            return view('penghuni-registrations.expired');
        }

        $paymentLinkUrl = $created['payment_link_url'] ?? null;
        $waSent = false;

        if (filled($paymentLinkUrl) && filled($created['penghuni']?->telepon)) {
            $nama = (string) ($created['penghuni']?->nama ?? 'Penghuni');
            $message = "Halo {$nama}, pendaftaran sewa Anda sudah diterima. Silakan lanjutkan pembayaran melalui link berikut: {$paymentLinkUrl}";
            $waSent = app(WhatsAppService::class)->sendByType(
                'link_pembayaran',
                (string) $created['penghuni']->telepon,
                [
                    'nama' => $nama,
                    'payment_link' => $paymentLinkUrl,
                ],
                $message
            );
        }

        return view('penghuni-registrations.success', [
            'paymentLinkUrl' => $paymentLinkUrl,
            'waSent' => $waSent,
        ]);
    }
}
