<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\KamarFloor;
use App\Models\KamarTipeHarga;
use App\Models\Sewa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KamarController extends Controller
{
    public function index()
    {
        KamarFloor::syncFromKamars();

        $kamars = Kamar::orderBy('nomor')->get();
        $floorsByNumber = KamarFloor::orderBy('number')->pluck('name', 'number');

        return view('kamars.index', compact('kamars', 'floorsByNumber'));
    }

    public function sewa()
    {
        KamarFloor::syncFromKamars();
        $floors = KamarFloor::orderBy('number')->get();

        $kamars = Kamar::with([
            'sewas' => fn($q) => $q->with('penghuni')->latest(),
        ])->orderBy('nomor')->get();

        $counts = [
            'tersedia'  => $kamars->where('status', 'tersedia')->count(),
            'terisi'    => $kamars->where('status', 'terisi')->count(),
            'reservasi' => $kamars->where('status', 'reservasi')->count(),
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
            'kamar' => new Kamar(),
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
        return redirect()->route('kamars.edit', $kamar);
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
            'nomor' => ['required', 'string', 'max:30', 'unique:kamars,nomor,' . $kamar->id],
            'tipe' => ['required', 'string', 'max:100', Rule::exists('kamar_tipe_hargas', 'tipe')],
            'status' => ['required', 'in:tersedia,terisi,perbaikan'],
            'layout_floor' => ['required', 'integer', Rule::exists('kamar_floors', 'number')],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $validated['harga_bulanan'] = KamarTipeHarga::where('tipe', $validated['tipe'])->value('harga_1_bulan') ?? 0;

        $kamar->update($validated);

        // If kamar is set to tersedia, close any active sewas for it
        if ($validated['status'] === 'tersedia') {
            \App\Models\Sewa::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->update(['status' => 'selesai']);
        }

        return redirect()->route('kamars.index')->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function selesaiSewa(Kamar $kamar)
    {
        Sewa::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        $kamar->update(['status' => 'tersedia']);

        return redirect()->route('kamars.sewa')->with('success', 'Sewa kamar ' . $kamar->nomor . ' telah diselesaikan.');
    }

    public function perpanjangSewa(Request $request, Kamar $kamar)
    {
        $request->validate([
            'tanggal_keluar' => ['required', 'date'],
            'biaya_bulanan'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = ['tanggal_keluar' => $request->tanggal_keluar];
        if ($request->filled('biaya_bulanan')) {
            $data['biaya_bulanan'] = $request->biaya_bulanan;
        }

        Sewa::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->update($data);

        return redirect()->route('kamars.sewa')->with('success', 'Sewa kamar ' . $kamar->nomor . ' berhasil diperpanjang.');
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
        $kamar->delete();

        return redirect()->route('kamars.index')->with('success', 'Data kamar berhasil dihapus.');
    }
}
