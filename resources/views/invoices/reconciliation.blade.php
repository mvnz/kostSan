@php $pageTitle = 'Rekonsiliasi Invoice'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Rekonsiliasi Invoice</h2><p>Temukan data yang perlu diperiksa sebelum menutup pembukuan.</p></div>
<div class="alert alert-info">Laporan ini hanya membaca data. Invoice manual tidak dianggap bermasalah. Hubungan invoice lama tidak ditentukan otomatis; periksa bukti transaksi sebelum melakukan koreksi. Laporan ini belum mencocokkan buku Keuangan.</div>
<form method="GET" action="{{ route('invoices.reconciliation') }}" class="d-flex flex-wrap align-items-end gap-2 mb-3">
<input type="hidden" name="kategori" value="{{ $kategori }}">
<div><label for="bulan" class="form-label">Bulan rekonsiliasi</label><input type="month" id="bulan" name="bulan" value="{{ $bulan }}" class="form-control"></div>
<button type="submit" class="btn btn-primary">Terapkan</button>
<a href="{{ route('invoices.reconciliation', ['kategori' => $kategori]) }}" class="btn btn-outline-secondary">Semua bulan</a>
</form>
<p>Jumlah temuan mengikuti bulan pilihan. Invoice tidak sesuai ditampilkan bila periode invoice atau pembayaran asal berada pada bulan tersebut.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
@foreach(['legacy' => 'Invoice AUTO lama tanpa sumber', 'tanpa_invoice' => 'Pembayaran tanpa invoice', 'tidak_sesuai' => 'Invoice tidak sesuai pembayaran'] as $key => $label)
<a class="btn {{ $kategori === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('invoices.reconciliation', ['kategori' => $key, 'bulan' => $bulan]) }}">{{ $label }} ({{ $counts[$key] }})</a>
@endforeach
</div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table">
<thead><tr><th>Sumber</th><th>Penghuni / kamar</th><th>Periode</th><th>Nominal</th><th>Perlu diperiksa</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr>
@if($kategori === 'tanpa_invoice')
<td>Pembayaran #{{ $record->id }}</td><td>{{ $record->sewa?->penghuni?->nama ?? '-' }}<br>Kamar {{ $record->sewa?->kamar?->nomor ?? '-' }}</td>
<td>{{ $record->periode?->format('m/Y') }}</td><td>Rp {{ number_format((float) $record->jumlah, 2, ',', '.') }}</td><td>Belum ada invoice untuk pembayaran ini.</td>
@else
<td>{{ $record->nomor_invoice }}@if($record->payment_id)<br>Pembayaran #{{ $record->payment_id }}@endif</td><td>{{ $record->penghuni?->nama ?? '-' }}@if($record->payment_id)<br>Kamar {{ $record->payment?->sewa?->kamar?->nomor ?? '-' }}@endif</td>
<td>{{ $record->periode?->format('m/Y') }}</td><td>Rp {{ number_format((float) $record->jumlah_tagihan, 2, ',', '.') }}</td>
<td>@if($kategori === 'legacy')Invoice AUTO belum terhubung. Cocokkan penghuni, periode, nominal, dan bukti pembayaran.@else
Bandingkan penghuni, periode, nominal, dan status dengan pembayaran asal.<br>Pembayaran: Rp {{ number_format((float) $record->payment?->jumlah, 2, ',', '.') }} · {{ $record->payment?->periode?->format('m/Y') }} · {{ $record->payment?->status }}<br>Invoice: {{ $record->status }}
@endif</td>
@endif
</tr>
@empty
<tr><td colspan="5">Tidak ada data pada kategori ini.</td></tr>
@endforelse
</tbody></table></div>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3"><span>{{ $records->total() }} data · Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}</span><div class="d-flex gap-2">
@if($records->previousPageUrl())<a class="btn btn-outline-secondary" href="{{ $records->previousPageUrl() }}">Sebelumnya</a>@endif
@if($records->nextPageUrl())<a class="btn btn-outline-secondary" href="{{ $records->nextPageUrl() }}">Berikutnya</a>@endif
</div><a href="{{ route('invoices.index') }}" class="btn btn-secondary">Kembali ke Invoice</a></div>
</div></div>
@endsection
