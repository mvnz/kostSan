<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\KostProfile;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SewaController extends Controller
{
    public function index()
    {
        $sewas = Sewa::with(['kamar', 'penghuni'])->latest()->get();
        $pembayarans = Pembayaran::with('sewa.kamar', 'sewa.penghuni')->latest('periode')->get();

        return view('sewas.index', compact('sewas', 'pembayarans'));
    }

    public function pilihKamar()
    {
        $kamars = Kamar::orderBy('nomor')->get();
        $counts = [
            'tersedia'  => $kamars->where('status', 'tersedia')->count(),
            'terisi'    => $kamars->where('status', 'terisi')->count(),
            'reservasi' => $kamars->where('status', 'reservasi')->count(),
            'perbaikan' => $kamars->where('status', 'perbaikan')->count(),
        ];
        return view('sewas.pilih-kamar', compact('kamars', 'counts'));
    }

    public function create()
    {
        $kamars = Kamar::orderBy('nomor')->get();
        $selectedKamarId = old('kamar_id', request('kamar_id'));
        $selectedHarga = $selectedKamarId ? (float) ($kamars->firstWhere('id', $selectedKamarId)?->harga_bulanan ?? 0) : 0;
        $biayaTambahan = 0;

        return view('sewas.form', [
            'sewa'            => new Sewa(),
            'kamars'          => $kamars,
            'penghunis'       => Penghuni::orderBy('nama')->get(),
            'selectedKamarId' => $selectedKamarId,
            'selectedHarga'   => $selectedHarga,
            'biayaTambahan'   => $biayaTambahan,
            'lamaSewa'        => 1,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kamar_id' => ['required', 'exists:kamars,id'],
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'biaya_bulanan' => ['required', 'numeric', 'min:0'],
            'uang_jaminan' => ['nullable', 'numeric', 'min:0'],
            'cara_pembayaran' => ['nullable', 'string', 'max:100'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'status' => ['required', 'in:aktif,selesai,menunggak'],
            'catatan' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('bukti_pembayaran')) {
            $validated['bukti_pembayaran'] = $request->file('bukti_pembayaran')->store('bukti-sewa', 'local');
        }

        $sewa = Sewa::create($validated);

        // If this sewa is aktif, end other active sewas for the same kamar
        if ($validated['status'] === 'aktif') {
            Sewa::where('kamar_id', $sewa->kamar_id)
                ->where('id', '!=', $sewa->id)
                ->where('status', 'aktif')
                ->update(['status' => 'selesai']);
        }

        $this->syncKamarStatus($sewa->kamar_id);

        $sewa->loadMissing(['penghuni', 'kamar']);
        if (filled($sewa->penghuni?->telepon)) {
            app(WhatsAppService::class)->sendByType(
                'sewa_aktif',
                (string) $sewa->penghuni->telepon,
                [
                    'nama' => (string) ($sewa->penghuni->nama ?? 'Penghuni'),
                    'nomor_kamar' => (string) ($sewa->kamar->nomor ?? '-'),
                    'tanggal_masuk' => (string) ($sewa->tanggal_masuk?->format('d-m-Y') ?? '-'),
                ],
                "Halo {$sewa->penghuni->nama}, sewa kamar {$sewa->kamar->nomor} sudah aktif mulai {$sewa->tanggal_masuk?->format('d-m-Y')}."
            );
        }

        return redirect()->route('kamars.sewa')->with('success', 'Data sewa berhasil ditambahkan.');
    }

    public function show(Sewa $sewa)
    {
        $sewa->load(['kamar', 'penghuni', 'pembayarans']);
        $nama    = $sewa->penghuni->nama ?? 'Unknown';
        $parts   = explode(' ', trim($nama));
        $inisial = strtoupper(mb_substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(mb_substr($parts[1], 0, 1)) : '');
        $bulan   = ($sewa->tanggal_masuk && $sewa->tanggal_keluar)
            ? max(1, $sewa->tanggal_masuk->diffInMonths($sewa->tanggal_keluar))
            : 1;
        $total   = (float) $sewa->biaya_bulanan * $bulan;
        return view('sewas.show', compact('sewa', 'inisial', 'bulan', 'total'));
    }

    public function edit(Sewa $sewa)
    {
        $kamars = Kamar::orderBy('nomor')->get();
        $selectedKamarId = $sewa->kamar_id;
        $selectedHarga = (float) ($kamars->firstWhere('id', $selectedKamarId)?->harga_bulanan ?? 0);
        $biayaTambahan = max(0, (float) $sewa->biaya_bulanan - $selectedHarga);

        $lamaSewa = 1;
        if ($sewa->tanggal_masuk && $sewa->tanggal_keluar) {
            $lamaSewa = max(1, min(12, (int) $sewa->tanggal_masuk->diffInMonths($sewa->tanggal_keluar)));
        }

        return view('sewas.form', [
            'sewa'            => $sewa,
            'kamars'          => $kamars,
            'penghunis'       => Penghuni::orderBy('nama')->get(),
            'selectedKamarId' => $selectedKamarId,
            'selectedHarga'   => $selectedHarga,
            'biayaTambahan'   => $biayaTambahan,
            'lamaSewa'        => $lamaSewa,
        ]);
    }

    public function update(Request $request, Sewa $sewa)
    {
        $validated = $request->validate([
            'kamar_id' => ['required', 'exists:kamars,id'],
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'biaya_bulanan' => ['required', 'numeric', 'min:0'],
            'uang_jaminan' => ['nullable', 'numeric', 'min:0'],
            'cara_pembayaran' => ['nullable', 'string', 'max:100'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'status' => ['required', 'in:aktif,selesai,menunggak'],
            'catatan' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('bukti_pembayaran')) {
            // Delete old file if exists
            if ($sewa->bukti_pembayaran) {
                Storage::disk('local')->delete($sewa->bukti_pembayaran);
            }
            $validated['bukti_pembayaran'] = $request->file('bukti_pembayaran')->store('bukti-sewa', 'local');
        } else {
            unset($validated['bukti_pembayaran']);
        }

        $oldKamarId = $sewa->kamar_id;
        $sewa->update($validated);

        $this->syncKamarStatus($oldKamarId);
        $this->syncKamarStatus($sewa->kamar_id);

        return redirect()->route('sewas.index')->with('success', 'Data sewa berhasil diperbarui.');
    }

    public function destroy(Sewa $sewa)
    {
        $kamarId = $sewa->kamar_id;
        $sewa->delete();
        $this->syncKamarStatus($kamarId);

        return redirect()->route('sewas.index')->with('success', 'Data sewa berhasil dihapus.');
    }

    private function syncKamarStatus(int $kamarId): void
    {
        $kamar = Kamar::find($kamarId);
        if ($kamar === null || $kamar->status === 'perbaikan') {
            return;
        }

        $aktif = Sewa::where('kamar_id', $kamarId)->where('status', 'aktif')->exists();
        $kamar->update(['status' => $aktif ? 'terisi' : 'tersedia']);
    }

    public function kontrak(Sewa $sewa)
    {
        $sewa->load('kamar', 'penghuni');
        $profile = KostProfile::first();

        $pdf = Pdf::loadView('sewas.kontrak', compact('sewa', 'profile'))
            ->setPaper('a4', 'portrait');

        $nama = str_replace(' ', '-', strtolower($sewa->penghuni?->nama ?? 'kontrak'));

        return $pdf->download("kontrak-sewa-{$nama}.pdf");
    }
}
