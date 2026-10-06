<?php

namespace App\Http\Controllers;

use App\Models\Penghuni;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PenghuniController extends Controller
{
    public function index()
    {
        $penghunis = Penghuni::latest()->get();

        return view('penghunis.index', compact('penghunis'));
    }

    public function create()
    {
        return view('penghunis.form', ['penghuni' => new Penghuni()]);
    }

    public function store(Request $request)
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
            'lama_sewa_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
            'kontak_darurat_nama' => ['nullable', 'string', 'max:150'],
            'kontak_darurat_hubungan' => ['nullable', 'string', 'max:100'],
            'kontak_darurat_telepon' => ['nullable', 'string', 'max:50'],
            'jenis_kendaraan' => ['nullable', 'in:tidak_ada,motor'],
            'kendaraan_merek_tipe' => ['nullable', 'string', 'max:100'],
            'kendaraan_warna' => ['nullable', 'string', 'max:50'],
            'kendaraan_nomor_polisi' => ['nullable', 'string', 'max:30'],
            'harga_sewa' => ['nullable', 'numeric', 'min:0'],
            'tanggal_jatuh_tempo' => ['nullable', 'date'],
            'setuju_peraturan' => ['nullable', 'boolean'],
            'catatan_pengelola' => ['nullable', 'string'],
        ]);

        $validated['setuju_peraturan'] = $request->boolean('setuju_peraturan');

        Penghuni::create($validated);

        return redirect()->route('penghunis.index')->with('success', 'Data penghuni berhasil ditambahkan.');
    }

    public function show(Penghuni $penghuni)
    {
        return redirect()->route('penghunis.edit', $penghuni);
    }

    public function edit(Penghuni $penghuni)
    {
        return view('penghunis.form', compact('penghuni'));
    }

    public function update(Request $request, Penghuni $penghuni)
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
            'lama_sewa_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
            'kontak_darurat_nama' => ['nullable', 'string', 'max:150'],
            'kontak_darurat_hubungan' => ['nullable', 'string', 'max:100'],
            'kontak_darurat_telepon' => ['nullable', 'string', 'max:50'],
            'jenis_kendaraan' => ['nullable', 'in:tidak_ada,motor'],
            'kendaraan_merek_tipe' => ['nullable', 'string', 'max:100'],
            'kendaraan_warna' => ['nullable', 'string', 'max:50'],
            'kendaraan_nomor_polisi' => ['nullable', 'string', 'max:30'],
            'harga_sewa' => ['nullable', 'numeric', 'min:0'],
            'tanggal_jatuh_tempo' => ['nullable', 'date'],
            'setuju_peraturan' => ['nullable', 'boolean'],
            'catatan_pengelola' => ['nullable', 'string'],
        ]);

        $validated['setuju_peraturan'] = $request->boolean('setuju_peraturan');

        $penghuni->update($validated);

        return redirect()->route('penghunis.index')->with('success', 'Data penghuni berhasil diperbarui.');
    }

    public function destroy(Penghuni $penghuni)
    {
        $penghuni->loadCount(['sewas', 'reservasis', 'invoices']);

        if ($penghuni->sewas_count > 0 || $penghuni->reservasis_count > 0 || $penghuni->invoices_count > 0) {
            return redirect()
                ->route('penghunis.index')
                ->with('error', 'Data penghuni tidak dapat dihapus karena masih memiliki relasi pada data sewa, reservasi, atau invoice.');
        }

        try {
            $penghuni->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('penghunis.index')
                ->with('error', 'Data penghuni tidak dapat dihapus karena masih digunakan oleh data lain.');
        }

        return redirect()->route('penghunis.index')->with('success', 'Data penghuni berhasil dihapus.');
    }

    public function cetak()
    {
        $penghunis = Penghuni::orderBy('nama')->get();

        return view('penghunis.print', compact('penghunis'));
    }
}
