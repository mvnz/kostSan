<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Sewa;
use App\Services\RoomAvailability;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        return back()->with('success', $approved ? 'Pembayaran disetujui. Sewa yang sudah selesai tetap selesai.' : 'Pembayaran ini sudah disetujui sebelumnya.');
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
                    'sewa' => $sewa,
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
                'sewa_id' => $sewa->id,
                'periode' => $periode->toDateString(),
                'jumlah' => $sewa->biaya_bulanan,
                'metode' => 'cash',
                'status' => 'belum_lunas',
                'keterangan' => 'Tagihan bulk '.$periode->translatedFormat('F Y'),
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
