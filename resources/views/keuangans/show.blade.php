@php $pageTitle = 'Detail Keuangan'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Detail Keuangan #{{ $keuangan->id }}</h2><p>Rincian transaksi dan bukti pencatatan.</p></div>
<div class="card"><div class="card-body">
<dl class="row">
<dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">{{ $keuangan->tanggal?->format('d/m/Y') }}</dd>
<dt class="col-sm-3">Jenis</dt><dd class="col-sm-9">{{ $keuangan->jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran' }}</dd>
<dt class="col-sm-3">Kategori</dt><dd class="col-sm-9">{{ $keuangan->kategori }}</dd>
<dt class="col-sm-3">Jumlah</dt><dd class="col-sm-9">Rp {{ number_format((float) $keuangan->jumlah, 2, ',', '.') }}</dd>
<dt class="col-sm-3">Deskripsi</dt><dd class="col-sm-9 text-break">{{ $keuangan->deskripsi }}</dd>
<dt class="col-sm-3">Sumber</dt><dd class="col-sm-9">@if($keuangan->payment_id)Sumber otomatis · Pembayaran #{{ $keuangan->payment_id }}@elseif($keuangan->reversalSource)Pembalikan otomatis · Pembayaran #{{ $keuangan->reversalSource->payment_id }}@else Pencatatan manual @endif</dd>
<dt class="col-sm-3">Bukti</dt><dd class="col-sm-9">@if($keuangan->bukti_path)<a href="{{ route('secure-files.show', ['path' => $keuangan->bukti_path]) }}" target="_blank" rel="noopener">Lihat bukti</a>@else Belum ada bukti @endif</dd>
</dl>
@if($keuangan->payment_id || $keuangan->reversalSource)<div class="alert alert-info">Transaksi otomatis mengikuti pembayaran asal dan tidak dapat diubah atau dihapus. Koreksi harus menjaga riwayat transaksi.</div>@endif
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-secondary" href="{{ route('keuangans.index') }}">Kembali ke Keuangan</a>
@if($keuangan->payment_id || $keuangan->reversalSource)
@canMenu('manajemen_sewa.data_sewa', 'view')<a class="btn btn-outline-primary" href="{{ route('pembayarans.show', $keuangan->payment_id ?? $keuangan->reversalSource->payment_id) }}">Pembayaran asal</a>@endCanMenu
@else
@canMenu('keuangan.data_keuangan', 'update')<a class="btn btn-primary" href="{{ route('keuangans.edit', $keuangan) }}">Edit transaksi</a>@endCanMenu
@endif
</div></div></div>
@endsection
