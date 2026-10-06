<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\KamarFloor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KamarFloorController extends Controller
{
    public function index()
    {
        KamarFloor::syncFromKamars();

        $floors = KamarFloor::query()
            ->leftJoin('kamars', 'kamars.layout_floor', '=', 'kamar_floors.number')
            ->select('kamar_floors.*', DB::raw('COUNT(kamars.id) as kamar_count'))
            ->groupBy('kamar_floors.id', 'kamar_floors.number', 'kamar_floors.name', 'kamar_floors.created_at', 'kamar_floors.updated_at')
            ->orderBy('kamar_floors.number')
            ->get();

        return view('kamar-floors.index', compact('floors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'number' => ['nullable', 'integer', 'min:1', 'max:255', 'unique:kamar_floors,number'],
        ]);

        $nextNumber = (int) (KamarFloor::max('number') ?? 0) + 1;
        $number = (int) ($validated['number'] ?? $nextNumber);

        if ($number < 1 || $number > 255) {
            return back()->with('error', 'Nomor lantai harus antara 1 sampai 255.');
        }

        if (KamarFloor::where('number', $number)->exists()) {
            return back()->with('error', 'Nomor lantai sudah digunakan.');
        }

        KamarFloor::create([
            'number' => $number,
            'name' => $validated['name'],
        ]);

        return redirect()->route('kamar-floors.index')->with('success', 'Lantai baru berhasil ditambahkan.');
    }

    public function update(Request $request, KamarFloor $kamarFloor)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'number' => ['required', 'integer', 'min:1', 'max:255', 'unique:kamar_floors,number,' . $kamarFloor->id],
        ]);

        DB::transaction(function () use ($kamarFloor, $validated) {
            $oldNumber = (int) $kamarFloor->number;
            $newNumber = (int) $validated['number'];

            $kamarFloor->update([
                'name' => $validated['name'],
                'number' => $newNumber,
            ]);

            if ($oldNumber !== $newNumber) {
                Kamar::where('layout_floor', $oldNumber)->update(['layout_floor' => $newNumber]);
            }
        });

        return redirect()->route('kamar-floors.index')->with('success', 'Data lantai berhasil diperbarui.');
    }

    public function destroy(KamarFloor $kamarFloor)
    {
        $dipakaiKamar = Kamar::where('layout_floor', $kamarFloor->number)->exists();
        if ($dipakaiKamar) {
            return back()->with('error', 'Lantai tidak bisa dihapus karena masih dipakai kamar.');
        }

        if (KamarFloor::count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu lantai.');
        }

        $kamarFloor->delete();

        return redirect()->route('kamar-floors.index')->with('success', 'Lantai berhasil dihapus.');
    }
}
