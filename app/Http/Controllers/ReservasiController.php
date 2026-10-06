<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use Illuminate\Http\Request;

class ReservasiController extends Controller
{
    public function index()
    {
        $reservasis = Reservasi::with('kamar', 'penghuni')->latest('tanggal_reservasi')->get();

        return view('reservasis.index', compact('reservasis'));
    }

    public function create()
    {
        return view('reservasis.form', [
            'reservasi' => new Reservasi(),
            'kamars' => Kamar::orderBy('nomor')->get(),
            'penghunis' => Penghuni::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kamar_id' => ['required', 'exists:kamars,id'],
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'tanggal_reservasi' => ['required', 'date'],
            'rencana_masuk' => ['required', 'date'],
            'rencana_keluar' => ['nullable', 'date', 'after_or_equal:rencana_masuk'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:menunggu,dikonfirmasi,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ]);

        Reservasi::create($validated);

        return redirect()->route('reservasis.index')->with('success', 'Data reservasi berhasil ditambahkan.');
    }

    public function show(Reservasi $reservasi)
    {
        return redirect()->route('reservasis.edit', $reservasi);
    }

    public function edit(Reservasi $reservasi)
    {
        return view('reservasis.form', [
            'reservasi' => $reservasi,
            'kamars' => Kamar::orderBy('nomor')->get(),
            'penghunis' => Penghuni::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Reservasi $reservasi)
    {
        $validated = $request->validate([
            'kamar_id' => ['required', 'exists:kamars,id'],
            'penghuni_id' => ['required', 'exists:penghunis,id'],
            'tanggal_reservasi' => ['required', 'date'],
            'rencana_masuk' => ['required', 'date'],
            'rencana_keluar' => ['nullable', 'date', 'after_or_equal:rencana_masuk'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:menunggu,dikonfirmasi,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ]);

        $reservasi->update($validated);

        return redirect()->route('reservasis.index')->with('success', 'Data reservasi berhasil diperbarui.');
    }

    public function destroy(Reservasi $reservasi)
    {
        $reservasi->delete();

        return redirect()->route('reservasis.index')->with('success', 'Data reservasi berhasil dihapus.');
    }
}
