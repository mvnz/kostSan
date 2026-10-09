<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\KamarFloor;
use App\Models\KamarTipeHarga;
use App\Models\Sewa;
use App\Services\RoomAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KamarController extends Controller
{
    public function index()
    {
        KamarFloor::syncFromKamars();

        $kamars = Kamar::withCount(['sewas', 'reservasis', 'confirmedReservations'])->orderBy('nomor')->get();
        $floorsByNumber = KamarFloor::orderBy('number')->pluck('name', 'number');

        return view('kamars.index', compact('kamars', 'floorsByNumber'));
    }

    public function sewa()
    {
        KamarFloor::syncFromKamars();
        $floors = KamarFloor::orderBy('number')->get();

        $kamars = Kamar::with([
            'sewas' => fn ($q) => $q->with('penghuni')->latest(),
        ])->withCount('confirmedReservations')->orderBy('nomor')->get();

        $counts = [
            'tersedia' => $kamars->where('status', 'tersedia')->count(),
            'terisi' => $kamars->where('status', 'terisi')->count(),
            'reservasi' => $kamars->where('confirmed_reservations_count', '>', 0)->count(),
            'perbaikan' => $kamars->where('status', 'perbaikan')->count(),
        ];

        return view('kamars.sewa', compact('kamars', 'counts', 'floors'));
    }

    public function create()
    {
        KamarFloor::syncFromKamars();
        $floors = KamarFloor::orderBy('number')->get();
        $tipeHargas = KamarTipeHarga::orderBy('tipe')->get();

        return view('kamars.form', [
            'kamar' => new Kamar,
            'floors' => $floors,
            'tipeHargas' => $tipeHargas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor' => ['required', 'string', 'max:30', 'unique:kamars,nomor'],
            'tipe' => ['required', 'string', 'max:100', Rule::exists('kamar_tipe_hargas', 'tipe')],
            'status' => ['required', 'in:tersedia,terisi,perbaikan'],
            'layout_floor' => ['required', 'integer', Rule::exists('kamar_floors', 'number')],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $validated['harga_bulanan'] = KamarTipeHarga::where('tipe', $validated['tipe'])->value('harga_1_bulan') ?? 0;

        Kamar::create($validated);

        return redirect()->route('kamars.index')->with('success', 'Data kamar berhasil ditambahkan.');
    }

    public function show(Kamar $kamar)
    {
        $kamar->loadCount(['sewas', 'reservasis']);

        return view('kamars.show', compact('kamar'));
    }

    public function edit(Kamar $kamar)
    {
        KamarFloor::syncFromKamars();
        $floors = KamarFloor::orderBy('number')->get();
        $tipeHargas = KamarTipeHarga::orderBy('tipe')->get();

        return view('kamars.form', compact('kamar', 'floors', 'tipeHargas'));
    }

    public function update(Request $request, Kamar $kamar)
    {
        $validated = $request->validate([
            'nomor' => ['required', 'string', 'max:30', 'unique:kamars,nomor,'.$kamar->id],
            'tipe' => ['required', 'string', 'max:100', Rule::exists('kamar_tipe_hargas', 'tipe')],
            'status' => ['required', 'in:tersedia,terisi,perbaikan'],
            'layout_floor' => ['required', 'integer', Rule::exists('kamar_floors', 'number')],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $validated['harga_bulanan'] = KamarTipeHarga::where('tipe', $validated['tipe'])->value('harga_1_bulan') ?? 0;

        DB::transaction(function () use ($validated, $kamar): void {
            $kamar = Kamar::whereKey($kamar->id)->lockForUpdate()->firstOrFail();
            if ($validated['status'] === 'tersedia' && $kamar->sewas()->whereIn('status', ['aktif', 'menunggak'])->exists()) {
                throw ValidationException::withMessages(['status' => 'Kamar masih memiliki sewa aktif atau menunggak. Selesaikan sewa melalui Manajemen Sewa terlebih dahulu.']);
            }
            $kamar->update($validated);
        });

        return redirect()->route('kamars.index')->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function selesaiSewa(Kamar $kamar)
    {
        Sewa::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        $kamar->update(['status' => 'tersedia']);

        return redirect()->route('kamars.sewa')->with('success', 'Sewa kamar '.$kamar->nomor.' telah diselesaikan.');
    }

    public function perpanjangSewa(Request $request, Kamar $kamar)
    {
        $request->validate([
            'tanggal_keluar' => ['required', 'date'],
            'biaya_bulanan' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $kamar): void {
            $kamar = Kamar::whereKey($kamar->id)->lockForUpdate()->firstOrFail();
            $sewas = Sewa::where('kamar_id', $kamar->id)->where('status', 'aktif')->lockForUpdate()->get();
            if ($sewas->count() !== 1) {
                throw ValidationException::withMessages(['tanggal_keluar' => 'Perpanjangan membutuhkan tepat satu sewa aktif. Periksa Data Sewa.']);
            }
            $sewa = $sewas->first();
            $newEnd = Carbon::parse($request->tanggal_keluar);
            if ($newEnd->lessThanOrEqualTo($sewa->tanggal_keluar ?? $sewa->tanggal_masuk)) {
                throw ValidationException::withMessages(['tanggal_keluar' => 'Tanggal perpanjangan harus setelah tanggal akhir sewa sebelumnya.']);
            }
            app(RoomAvailability::class)->assertAvailable($kamar, $sewa->tanggal_masuk->toDateString(), $newEnd->toDateString(), exceptLease: $sewa->id, resident: $sewa->penghuni_id);
            $data = ['tanggal_keluar' => $newEnd->toDateString()];
            if ($request->filled('biaya_bulanan')) {
                $data['biaya_bulanan'] = $request->biaya_bulanan;
            }
            $sewa->update($data);
        });

        return redirect()->route('kamars.sewa')->with('success', 'Sewa kamar '.$kamar->nomor.' berhasil diperpanjang.');
    }

    public function updateLayout(Request $request, Kamar $kamar)
    {
        KamarFloor::syncFromKamars();

        $validated = $request->validate([
            'layout_floor' => ['required', 'integer', Rule::exists('kamar_floors', 'number')],
            'layout_row' => ['required', 'integer', 'min:1', 'max:30'],
            'layout_col' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $occupied = Kamar::query()
            ->where('id', '!=', $kamar->id)
            ->where('layout_floor', $validated['layout_floor'])
            ->where('layout_row', $validated['layout_row'])
            ->where('layout_col', $validated['layout_col'])
            ->exists();

        if ($occupied) {
            return back()->with('error', 'Posisi layout sudah dipakai kamar lain.');
        }

        $kamar->update($validated);

        return redirect()->route('kamars.sewa')->with('success', 'Posisi kamar berhasil diperbarui.');
    }

    public function destroy(Kamar $kamar)
    {
        $deleted = DB::transaction(function () use ($kamar): bool {
            $kamar = Kamar::whereKey($kamar->id)->lockForUpdate()->firstOrFail();
            if ($kamar->sewas()->exists() || $kamar->reservasis()->exists()) {
                return false;
            }

            $kamar->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('kamars.index')
                ->with('error', 'Kamar tidak dapat dihapus karena memiliki histori sewa atau reservasi. Pertahankan data untuk audit operasional.');
        }

        return redirect()->route('kamars.index')->with('success', 'Data kamar berhasil dihapus.');
    }
}
