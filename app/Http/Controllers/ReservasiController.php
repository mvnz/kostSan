<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Services\RoomAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'reservasi' => new Reservasi,
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
            'rencana_keluar' => ['nullable', 'date', 'after:rencana_masuk'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:menunggu,dikonfirmasi,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $kamar = Kamar::whereKey($validated['kamar_id'])->lockForUpdate()->firstOrFail();
            if ($validated['status'] === 'dikonfirmasi') {
                app(RoomAvailability::class)->assertAvailable($kamar, $validated['rencana_masuk'], $validated['rencana_keluar'] ?? null);
            }
            Reservasi::create($validated);
        });

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
            'rencana_keluar' => ['nullable', 'date', 'after:rencana_masuk'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:menunggu,dikonfirmasi,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $reservasi): void {
            $rooms = Kamar::whereIn('id', [$reservasi->kamar_id, $validated['kamar_id']])->orderBy('id')->lockForUpdate()->get();
            $reservasi = Reservasi::whereKey($reservasi->id)->lockForUpdate()->firstOrFail();
            $kamar = $rooms->firstWhere('id', $validated['kamar_id']);
            abort_unless($kamar, 404);
            if ($validated['status'] === 'dikonfirmasi') {
                app(RoomAvailability::class)->assertAvailable($kamar, $validated['rencana_masuk'], $validated['rencana_keluar'] ?? null, exceptReservation: $reservasi->id);
            }
            $reservasi->update($validated);
        });

        return redirect()->route('reservasis.index')->with('success', 'Data reservasi berhasil diperbarui.');
    }

    public function destroy(Reservasi $reservasi)
    {
        $reservasi->delete();

        return redirect()->route('reservasis.index')->with('success', 'Data reservasi berhasil dihapus.');
    }
}
