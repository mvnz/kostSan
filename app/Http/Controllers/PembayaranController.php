<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Sewa;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    public function index()
    {
        $pembayarans = Pembayaran::with('sewa.kamar', 'sewa.penghuni')->latest('periode')->get();

        return view('pembayarans.index', compact('pembayarans'));
    }

    public function create()
    {
        return view('pembayarans.form', [
            'pembayaran' => new Pembayaran(),
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
        ]);

        // Pembayaran baru selalu menunggu approval pemilik.
        $validated['status'] = 'belum_lunas';

        if ($request->hasFile('bukti_pembayaran')) {
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran')->store('bukti-pembayaran-sewa', 'local');
        }

        $pembayaran = Pembayaran::create($validated);

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil ditambahkan dan menunggu approval pemilik.');
    }

    public function show(Pembayaran $pembayaran)
    {
        return redirect()->route('pembayarans.edit', $pembayaran);
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
        ]);

        // Hindari bypass approval dari form edit.
        if ($pembayaran->status !== 'lunas') {
            $validated['status'] = 'belum_lunas';
        }

        if ($request->hasFile('bukti_pembayaran')) {
            if ($pembayaran->bukti_pembayaran_path) {
                Storage::disk('local')->delete($pembayaran->bukti_pembayaran_path);
            }
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran')->store('bukti-pembayaran-sewa', 'local');
        }

        $pembayaran->update($validated);

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function destroy(Pembayaran $pembayaran)
    {
        $pembayaran->delete();

        return redirect()->route('pembayarans.index')->with('success', 'Data pembayaran berhasil dihapus.');
    }

    public function approve(Pembayaran $pembayaran)
    {
        DB::transaction(function () use ($pembayaran): void {
            $pembayaran->loadMissing('sewa.kamar', 'sewa.penghuni');

            if ($pembayaran->status !== 'lunas') {
                $pembayaran->update([
                    'status' => 'lunas',
                    'tanggal_bayar' => $pembayaran->tanggal_bayar ?? now()->toDateString(),
                    'keterangan' => trim(($pembayaran->keterangan ?? '') . ' [Approved admin]'),
                ]);
            }

            $sewa = $pembayaran->sewa;
            if ($sewa) {
                Sewa::where('kamar_id', $sewa->kamar_id)
                    ->where('id', '!=', $sewa->id)
                    ->where('status', 'aktif')
                    ->update(['status' => 'selesai']);

                $sewa->update(['status' => 'aktif']);
                $sewa->kamar?->update(['status' => 'terisi']);
            }

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
        });

        return back()->with('success', 'Pembayaran disetujui. Status sewa aktif dan kamar sudah terisi.');
    }

    public function bulkBilling(Request $request)
    {
        $bulan = $request->string('bulan')->toString();
        if ($bulan === '') {
            $bulan = Carbon::now()->format('Y-m');
        }

        $periode = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();

        $sewas = Sewa::with('kamar', 'penghuni')
            ->where('status', 'aktif')
            ->get()
            ->map(function (Sewa $sewa) use ($periode): array {
                $sudahAda = $sewa->pembayarans()
                    ->whereYear('periode', $periode->year)
                    ->whereMonth('periode', $periode->month)
                    ->exists();
                return [
                    'sewa'      => $sewa,
                    'sudah_ada' => $sudahAda,
                ];
            });

        return view('pembayarans.bulk-billing', compact('sewas', 'bulan', 'periode'));
    }

    public function storeBulkBilling(Request $request)
    {
        $bulan = $request->string('bulan')->toString();
        if ($bulan === '') {
            $bulan = Carbon::now()->format('Y-m');
        }

        $periode = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();

        $sewas = Sewa::with('penghuni', 'kamar')->where('status', 'aktif')->get();

        $dibuat = 0;
        $sudahAda = 0;

        foreach ($sewas as $sewa) {
            $exists = $sewa->pembayarans()
                ->whereYear('periode', $periode->year)
                ->whereMonth('periode', $periode->month)
                ->exists();

            if ($exists) {
                $sudahAda++;
                continue;
            }

            Pembayaran::create([
                'sewa_id'  => $sewa->id,
                'periode'  => $periode->toDateString(),
                'jumlah'   => $sewa->biaya_bulanan,
                'metode'   => 'cash',
                'status'   => 'belum_lunas',
                'keterangan' => 'Tagihan bulk ' . $periode->translatedFormat('F Y'),
            ]);

            $dibuat++;
        }

        $msg = "{$dibuat} tagihan berhasil dibuat untuk periode {$periode->translatedFormat('F Y')}.";
        if ($sudahAda > 0) {
            $msg .= " {$sudahAda} sewa sudah memiliki tagihan.";
        }

        return redirect()->route('pembayarans.index')->with('success', $msg);
    }
}
