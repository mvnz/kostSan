@php $pageTitle = 'Rekonsiliasi Pembayaran–Keuangan'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Rekonsiliasi Pembayaran–Keuangan</h2><p>Periksa pembayaran dan sumber pemasukan sebelum menutup pembukuan.</p></div>
<div class="alert alert-info">Pembayaran lunas tanpa sumber mungkin sudah dicatat sebagai pemasukan manual. Sistem hanya menawarkan kandidat dengan tanggal, nominal, jenis, dan kategori yang persis cocok; operator tetap wajib mencocokkan deskripsi/bukti. Tidak ada pencocokan otomatis.</div>
<form method="GET" action="{{ route('keuangans.reconciliation') }}" class="d-flex flex-wrap align-items-end gap-2 mb-3">
<input type="hidden" name="kategori" value="{{ $kategori }}">
<div><label for="bulan" class="form-label">Bulan pembayaran</label><input type="month" id="bulan" name="bulan" value="{{ $bulan }}" class="form-control"></div>
<button class="btn btn-primary" type="submit">Terapkan</button>
<a class="btn btn-outline-secondary" href="{{ route('keuangans.reconciliation', ['kategori' => $kategori]) }}">Semua bulan</a>
</form>
<p>Bulan mengikuti tanggal bayar, atau periode bila tanggal bayar kosong. Pemasukan berbeda tanggal juga muncul pada bulan pencatatannya. Jumlah nol hanya berlaku untuk cakupan laporan ini.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
@foreach(['tanpa_pemasukan' => 'Pembayaran lunas tanpa sumber pemasukan', 'tidak_sesuai' => 'Pemasukan tidak sesuai pembayaran'] as $key => $label)
<a class="btn {{ $kategori === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('keuangans.reconciliation', ['kategori' => $key, 'bulan' => $bulan]) }}">{{ $label }} ({{ $counts[$key] }})</a>
@endforeach
</div>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table">
<thead><tr><th>Pembayaran</th><th>Penghuni / kamar</th><th>Tanggal bayar / periode</th><th>Nominal dan status</th><th>Perlu diperiksa</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>Pembayaran #{{ $record->id }}</td><td>{{ $record->sewa?->penghuni?->nama ?? '-' }}<br>Kamar {{ $record->sewa?->kamar?->nomor ?? '-' }}</td>
<td>{{ $record->tanggal_bayar?->format('d/m/Y') ?? 'Tanggal bayar belum tercatat' }}<br>Periode {{ $record->periode?->format('m/Y') }}</td>
<td>Rp {{ number_format((float) $record->jumlah, 2, ',', '.') }}<br>{{ $record->status }}</td>
<td>@if($kategori === 'tanpa_pemasukan')
@php($matches = $candidates->get($record->id, collect()))
@if($matches->isEmpty())Cocokkan pemasukan manual dan bukti pembayaran sebelum koreksi. Tidak ada kandidat persis cocok.
@else
<div class="mb-2">Kandidat persis cocok:</div>
@foreach($matches as $candidate)<div><a href="{{ route('keuangans.show', $candidate) }}">Keuangan #{{ $candidate->id }}</a> · {{ $candidate->deskripsi }}</div>@endforeach
@if(auth()->user()->hasMenuPermission('keuangan.data_keuangan', 'update') && auth()->user()->hasMenuPermission('manajemen_sewa.data_sewa', 'update'))
<form method="POST" action="{{ route('keuangans.reconciliation.attach', $record) }}" class="mt-2" onsubmit="return confirm('Tautkan pemasukan manual yang dipilih? Setelah ditautkan transaksi menjadi immutable sampai tautan dilepas dengan alasan audit.')">@csrf
@if($bulan)<input type="hidden" name="bulan" value="{{ $bulan }}">@endif
<label class="form-label" for="candidate-{{ $record->id }}">Pemasukan</label><select class="form-select form-select-sm" id="candidate-{{ $record->id }}" name="keuangan_id" required>@foreach($matches as $candidate)<option value="{{ $candidate->id }}">#{{ $candidate->id }} · {{ $candidate->tanggal?->format('d/m/Y') }} · Rp {{ number_format((float) $candidate->jumlah, 2, ',', '.') }}</option>@endforeach</select>
<label class="form-label mt-2" for="reason-{{ $record->id }}">Alasan pencocokan</label><input class="form-control form-control-sm" id="reason-{{ $record->id }}" name="reason" minlength="10" maxlength="500" required>
<button class="btn btn-sm btn-warning mt-2" type="submit">Tautkan dengan audit</button>
</form>
@endif
@endif
@else
Keuangan #{{ $record->ledgerEntry->id }}: Rp {{ number_format((float) $record->ledgerEntry->jumlah, 2, ',', '.') }}<br>{{ $record->ledgerEntry->tanggal?->format('d/m/Y') }} · {{ $record->ledgerEntry->jenis }} · {{ $record->ledgerEntry->kategori }}<br>Bandingkan nominal, tanggal, jenis, kategori dan status asal.
@endif</td></tr>
@empty<tr><td colspan="5">Tidak ada data pada kategori ini.</td></tr>@endforelse
</tbody></table></div>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3"><span>{{ $records->total() }} data · Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}</span><div class="d-flex gap-2">
@if($records->previousPageUrl())<a class="btn btn-outline-secondary" href="{{ $records->previousPageUrl() }}">Sebelumnya</a>@endif
@if($records->nextPageUrl())<a class="btn btn-outline-secondary" href="{{ $records->nextPageUrl() }}">Berikutnya</a>@endif
</div><a class="btn btn-secondary" href="{{ route('keuangans.index') }}">Kembali ke Keuangan</a></div>
</div></div>
@endsection
