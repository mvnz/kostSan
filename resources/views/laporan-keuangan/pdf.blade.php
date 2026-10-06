<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Keuangan {{ $periodeLabel }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; background: #fff; }
    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 16px; }
    .header h1 { font-size: 16px; font-weight: 700; }
    .header h2 { font-size: 13px; font-weight: 600; color: #555; margin-top: 2px; }
    .header p { font-size: 10px; color: #777; margin-top: 4px; }
    .summary { display: table; width: 100%; margin-bottom: 16px; }
    .summary-box { display: table-cell; width: 33.33%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; vertical-align: top; }
    .summary-box + .summary-box { margin-left: 8px; }
    .summary-label { font-size: 9px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .summary-value { font-size: 14px; font-weight: 800; margin-top: 2px; }
    .val-masuk { color: #16a34a; }
    .val-keluar { color: #dc2626; }
    .val-saldo { color: #2563eb; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.data th { background: #f1f5f9; font-size: 10px; font-weight: 700; padding: 6px 8px; border: 1px solid #e2e8f0; text-align: left; }
    table.data td { font-size: 10px; padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: middle; }
    table.data tr:nth-child(even) td { background: #f9fafb; }
    .badge-masuk { background: #dcfce7; color: #15803d; border-radius: 4px; padding: 1px 6px; font-size: 9px; font-weight: 700; }
    .badge-keluar { background: #fee2e2; color: #b91c1c; border-radius: 4px; padding: 1px 6px; font-size: 9px; font-weight: 700; }
    .text-right { text-align: right; }
    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $profile?->nama_kost ?? 'Kost' }}</h1>
    <h2>Laporan Keuangan — {{ $periodeLabel }}</h2>
    @if($profile?->alamat)
    <p>{{ $profile->alamat }}</p>
    @endif
</div>

{{-- Summary --}}
<table style="width:100%;margin-bottom:16px;">
    <tr>
        <td style="width:33%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;">
            <div style="font-size:9px;color:#888;font-weight:700;text-transform:uppercase;">Total Pemasukan</div>
            <div style="font-size:14px;font-weight:800;color:#16a34a;margin-top:2px;">Rp {{ number_format((float)$totalPemasukan,0,',','.') }}</div>
        </td>
        <td style="width:4px;"></td>
        <td style="width:33%;padding:8px 10px;border:1px solid #ddd;">
            <div style="font-size:9px;color:#888;font-weight:700;text-transform:uppercase;">Total Pengeluaran</div>
            <div style="font-size:14px;font-weight:800;color:#dc2626;margin-top:2px;">Rp {{ number_format((float)$totalPengeluaran,0,',','.') }}</div>
        </td>
        <td style="width:4px;"></td>
        <td style="width:33%;padding:8px 10px;border:1px solid #ddd;">
            <div style="font-size:9px;color:#888;font-weight:700;text-transform:uppercase;">Saldo</div>
            <div style="font-size:14px;font-weight:800;color:{{ $saldo >= 0 ? '#2563eb' : '#dc2626' }};margin-top:2px;">Rp {{ number_format((float)$saldo,0,',','.') }}</div>
        </td>
    </tr>
</table>

{{-- Detail Table --}}
<table class="data">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:12%">Tanggal</th>
            <th style="width:14%">Jenis</th>
            <th style="width:20%">Kategori</th>
            <th style="width:18%;text-align:right">Nominal</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ optional($item->tanggal)->format('d/m/Y') }}</td>
            <td>
                @if($item->jenis === 'pemasukan')
                    <span class="badge-masuk">Pemasukan</span>
                @else
                    <span class="badge-keluar">Pengeluaran</span>
                @endif
            </td>
            <td>{{ $item->kategori }}</td>
            <td class="text-right">Rp {{ number_format((float)$item->jumlah,0,',','.') }}</td>
            <td>{{ $item->deskripsi ?: '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;color:#aaa;padding:12px;">Tidak ada data untuk periode ini.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:700;padding:6px 8px;background:#f1f5f9;">Total Pemasukan</td>
            <td class="text-right" style="font-weight:700;color:#16a34a;background:#f1f5f9;">Rp {{ number_format((float)$totalPemasukan,0,',','.') }}</td>
            <td style="background:#f1f5f9;"></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:700;padding:6px 8px;background:#f1f5f9;">Total Pengeluaran</td>
            <td class="text-right" style="font-weight:700;color:#dc2626;background:#f1f5f9;">Rp {{ number_format((float)$totalPengeluaran,0,',','.') }}</td>
            <td style="background:#f1f5f9;"></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:700;padding:6px 8px;background:#e8f0fe;">Saldo</td>
            <td class="text-right" style="font-weight:700;color:#2563eb;background:#e8f0fe;">Rp {{ number_format((float)$saldo,0,',','.') }}</td>
            <td style="background:#e8f0fe;"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
