<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kontrak Sewa — {{ $sewa->penghuni?->nama }}</title>
<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'DejaVu Sans',sans-serif; font-size:11px; color:#1a1a1a; }
    .header { text-align:center; border-bottom:3px double #333; padding-bottom:10px; margin-bottom:18px; }
    .header h1 { font-size:15px; font-weight:700; text-transform:uppercase; letter-spacing:1px; }
    .header h2 { font-size:12px; font-weight:600; margin-top:3px; }
    .header p  { font-size:10px; color:#555; margin-top:2px; }
    h3 { font-size:11px; font-weight:700; margin:14px 0 6px; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #ccc; padding-bottom:3px; }
    .pihak-table { width:100%; border-collapse:collapse; margin-bottom:10px; }
    .pihak-table td { padding:3px 6px; font-size:11px; vertical-align:top; }
    .pihak-table td:first-child { width:32%; color:#555; }
    .pasal { margin-bottom:12px; }
    .pasal-title { font-weight:700; margin-bottom:4px; }
    .pasal p, .pasal ul { font-size:11px; line-height:1.7; }
    .pasal ul { padding-left:16px; }
    .sign-table { width:100%; margin-top:30px; border-collapse:collapse; }
    .sign-table td { width:50%; text-align:center; padding:8px; vertical-align:bottom; font-size:11px; }
    .sign-box { border-top:1px solid #333; margin-top:60px; padding-top:4px; }
    .footer { margin-top:18px; font-size:9px; color:#aaa; text-align:center; border-top:1px solid #eee; padding-top:6px; }
    .highlight { background:#f5f8ff; padding:6px 10px; border-left:3px solid #4680ff; margin:8px 0; border-radius:2px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $profile?->nama_kost ?? 'Kost' }}</h1>
    <h2>Perjanjian Sewa Kamar</h2>
    @if($profile?->alamat)<p>{{ $profile->alamat }}</p>@endif
</div>

<div class="highlight">
    Perjanjian ini dibuat pada hari <strong>{{ now()->translatedFormat('l, d F Y') }}</strong>
    antara pihak-pihak berikut ini:
</div>

<h3>Pihak yang Bersepakat</h3>
<table class="pihak-table">
    <tr><td colspan="2"><strong>Pihak Pertama (Pemilik)</strong></td></tr>
    <tr><td>Nama</td><td>: {{ $profile?->pemilik ?? '-' }}</td></tr>
    <tr><td>Telepon</td><td>: {{ $profile?->telepon ?? '-' }}</td></tr>
    <tr><td>Alamat</td><td>: {{ $profile?->alamat ?? '-' }}</td></tr>
</table>
<table class="pihak-table">
    <tr><td colspan="2"><strong>Pihak Kedua (Penyewa)</strong></td></tr>
    <tr><td>Nama</td><td>: {{ $sewa->penghuni?->nama ?? '-' }}</td></tr>
    <tr><td>NIK / KTP</td><td>: {{ $sewa->penghuni?->nik ?? '-' }}</td></tr>
    <tr><td>Telepon</td><td>: {{ $sewa->penghuni?->telepon ?? '-' }}</td></tr>
    <tr><td>Alamat Asal</td><td>: {{ $sewa->penghuni?->alamat ?? '-' }}</td></tr>
</table>

<h3>Pasal 1 — Objek Sewa</h3>
<div class="pasal">
    <p>Pihak Pertama menyewakan kepada Pihak Kedua sebuah kamar kost dengan keterangan:</p>
    <table class="pihak-table" style="margin-top:6px;">
        <tr><td>Nomor Kamar</td><td>: {{ $sewa->kamar?->nomor ?? '-' }}</td></tr>
        <tr><td>Tipe</td><td>: {{ $sewa->kamar?->tipe ?? '-' }}</td></tr>
        <tr><td>Deskripsi</td><td>: {{ $sewa->kamar?->deskripsi ?? '-' }}</td></tr>
    </table>
</div>

<h3>Pasal 2 — Jangka Waktu Sewa</h3>
<div class="pasal">
    <table class="pihak-table">
        <tr><td>Tanggal Mulai</td><td>: {{ optional($sewa->tanggal_masuk)->format('d F Y') ?? '-' }}</td></tr>
        <tr><td>Tanggal Selesai</td><td>: {{ optional($sewa->tanggal_keluar)->format('d F Y') ?? 'Tidak terbatas' }}</td></tr>
    </table>
    <p style="margin-top:6px;">Perjanjian ini berlaku selama periode tersebut dan dapat diperpanjang atas persetujuan kedua belah pihak.</p>
</div>

<h3>Pasal 3 — Harga Sewa dan Pembayaran</h3>
<div class="pasal">
    <table class="pihak-table">
        <tr><td>Biaya Sewa</td><td>: <strong>Rp {{ number_format((float)$sewa->biaya_bulanan,0,',','.') }}</strong> per bulan</td></tr>
        @if($sewa->uang_jaminan)
        <tr><td>Uang Jaminan</td><td>: Rp {{ number_format((float)$sewa->uang_jaminan,0,',','.') }}</td></tr>
        @endif
        <tr><td>Cara Pembayaran</td><td>: {{ $sewa->cara_pembayaran ?? 'Cash' }}</td></tr>
    </table>
    <p style="margin-top:6px;">Pembayaran dilakukan setiap bulan dan dinyatakan sah setelah mendapat konfirmasi dari Pihak Pertama.</p>
</div>

<h3>Pasal 4 — Ketentuan Umum</h3>
<div class="pasal">
    <ul>
        <li>Pihak Kedua wajib menjaga kebersihan dan ketertiban kamar serta lingkungan sekitar.</li>
        <li>Dilarang membawa tamu menginap tanpa sepengetahuan Pihak Pertama.</li>
        <li>Keterlambatan pembayaran lebih dari 7 hari akan dikenakan teguran; lebih dari 30 hari dapat mengakibatkan pemutusan sewa.</li>
        <li>Kerusakan yang disebabkan oleh kelalaian Pihak Kedua menjadi tanggung jawab Pihak Kedua.</li>
        <li>Pihak Kedua wajib memberitahu minimal 14 hari sebelum meninggalkan kamar.</li>
        <li>Uang jaminan dikembalikan setelah pengecekan kondisi kamar saat kepindahan.</li>
    </ul>
</div>

<h3>Pasal 5 — Pemutusan Perjanjian</h3>
<div class="pasal">
    <p>Perjanjian ini dapat diputus sebelum jangka waktu habis apabila salah satu pihak melanggar ketentuan yang telah disepakati, dengan pemberitahuan minimal 14 hari sebelumnya.</p>
</div>

<p style="margin-top:14px;font-size:11px;">Demikian perjanjian ini dibuat dalam 2 (dua) rangkap, masing-masing mempunyai kekuatan hukum yang sama, dan ditandatangani oleh kedua belah pihak.</p>

<table class="sign-table">
    <tr>
        <td>
            Pihak Pertama<br>
            <em>(Pemilik Kost)</em>
            <div class="sign-box">{{ $profile?->pemilik ?? '________________' }}</div>
        </td>
        <td>
            Pihak Kedua<br>
            <em>(Penyewa)</em>
            <div class="sign-box">{{ $sewa->penghuni?->nama ?? '________________' }}</div>
        </td>
    </tr>
</table>

<div class="footer">Dokumen ini dicetak oleh sistem {{ $profile?->nama_kost ?? 'Kost' }} pada {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
