<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Penghuni</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; margin: 0; color: #111; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        p { margin: 0 0 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; font-size: 11px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .muted { color: #667085; font-size: 10px; }

        @media screen and (max-width: 767.98px) {
            body { padding: 8px; }
            h1 { font-size: 16px; }
            p { font-size: 12px; }
            th, td { font-size: 10px; padding: 5px; word-break: break-word; }

            /* Mobile: keep key columns only (No, Identitas, Pembayaran). */
            th:nth-child(3), td:nth-child(3),
            th:nth-child(4), td:nth-child(4),
            th:nth-child(6), td:nth-child(6) {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
<h1>Laporan Data Penghuni Kos</h1>
<p>Tanggal cetak: {{ now()->format('d/m/Y H:i') }}</p>
<table>
<thead>
    <tr>
        <th>No</th>
        <th>Identitas Penghuni</th>
        <th>Kontak Darurat</th>
        <th>Kendaraan</th>
        <th>Pembayaran</th>
        <th>Catatan</th>
    </tr>
</thead>
<tbody>
@forelse($penghunis as $penghuni)
<tr>
    <td>{{ $loop->iteration }}</td>
    <td>
        <strong>{{ $penghuni->nama }}</strong><br>
        NIK: {{ $penghuni->nik ?: '-' }}<br>
        HP: {{ $penghuni->telepon ?: '-' }}<br>
        Email: {{ $penghuni->email ?: '-' }}<br>
        TTL: {{ ($penghuni->tempat_lahir ?: '-') . ', ' . ($penghuni->tanggal_lahir ? $penghuni->tanggal_lahir->format('d/m/Y') : '-') }}<br>
        Pekerjaan: {{ $penghuni->pekerjaan ?: '-' }}<br>
        Kamar: {{ $penghuni->nomor_kamar ?: '-' }} | Kunci: {{ $penghuni->jumlah_kunci ?? '-' }}<br>
        Mulai tinggal: {{ $penghuni->tanggal_mulai_tinggal ? $penghuni->tanggal_mulai_tinggal->format('d/m/Y') : '-' }} | Lama sewa: {{ $penghuni->lama_sewa_bulan ? $penghuni->lama_sewa_bulan . ' bulan' : '-' }}
    </td>
    <td>
        Nama: {{ $penghuni->kontak_darurat_nama ?: '-' }}<br>
        Hubungan: {{ $penghuni->kontak_darurat_hubungan ?: '-' }}<br>
        HP: {{ $penghuni->kontak_darurat_telepon ?: '-' }}
    </td>
    <td>
        Jenis: {{ ($penghuni->jenis_kendaraan ?? 'tidak_ada') === 'motor' ? 'Motor' : 'Tidak ada' }}<br>
        Merek/Tipe: {{ $penghuni->kendaraan_merek_tipe ?: '-' }}<br>
        Warna: {{ $penghuni->kendaraan_warna ?: '-' }}<br>
        Nopol: {{ $penghuni->kendaraan_nomor_polisi ?: '-' }}
    </td>
    <td>
        Harga: {{ $penghuni->harga_sewa ? 'Rp ' . number_format((float) $penghuni->harga_sewa, 0, ',', '.') : '-' }}<br>
        Jatuh tempo: {{ $penghuni->tanggal_jatuh_tempo ? $penghuni->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}<br>
        Pernyataan: {{ $penghuni->setuju_peraturan ? 'Setuju' : 'Belum setuju' }}
    </td>
    <td>
        {{ $penghuni->catatan_pengelola ?: '-' }}
        @if($penghuni->alamat_ktp || $penghuni->alamat)
            <div class="muted" style="margin-top:4px;">
                Alamat KTP: {{ $penghuni->alamat_ktp ?: '-' }}<br>
                Alamat domisili: {{ $penghuni->alamat ?: '-' }}
            </div>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6">Belum ada data penghuni.</td></tr>
@endforelse
</tbody>
</table>
</body>
</html>
