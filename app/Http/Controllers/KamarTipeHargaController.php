<?php

namespace App\Http\Controllers;

use App\Models\KamarTipeHarga;
use Illuminate\Http\Request;

class KamarTipeHargaController extends Controller
{
    public function index()
    {
        $tipeHargas = KamarTipeHarga::query()->orderBy('tipe')->get();

        return view('kamar-tipe-hargas.index', compact('tipeHargas'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        KamarTipeHarga::create($validated);

        return redirect()->route('kamar-tipe-hargas.index')->with('success', 'Tipe kamar baru berhasil ditambahkan.');
    }

    public function update(Request $request, KamarTipeHarga $kamarTipeHarga)
    {
        $validated = $this->validated($request, $kamarTipeHarga->id);

        $kamarTipeHarga->update($validated);

        return redirect()->route('kamar-tipe-hargas.index')->with('success', 'Harga tipe kamar berhasil diperbarui.');
    }

    public function destroy(KamarTipeHarga $kamarTipeHarga)
    {
        $dipakaiKamar = \App\Models\Kamar::where('tipe', $kamarTipeHarga->tipe)->exists();
        if ($dipakaiKamar) {
            return back()->with('error', 'Tipe kamar tidak bisa dihapus karena masih dipakai kamar.');
        }

        $kamarTipeHarga->delete();

        return redirect()->route('kamar-tipe-hargas.index')->with('success', 'Tipe kamar berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tipe' => ['required', 'string', 'max:100', 'unique:kamar_tipe_hargas,tipe,' . $ignoreId],
            'harga_1_bulan' => ['required', 'numeric', 'min:0'],
            'harga_3_bulan' => ['required', 'numeric', 'min:0'],
            'harga_6_bulan' => ['required', 'numeric', 'min:0'],
            'harga_12_bulan' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
