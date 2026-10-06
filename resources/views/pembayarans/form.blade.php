@php
$pageTitle = $pembayaran->exists ? 'Edit Pembayaran' : 'Tambah Pembayaran';
$pageSubtitle = 'Form pembayaran sewa penghuni.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-money"></i></div>
    <div>
        <h2>{{ $pembayaran->exists ? 'Edit Pembayaran' : 'Tambah Pembayaran' }}</h2>
        <p>Isi detail periode dan nominal pembayaran.</p>
    </div>
</div>
<div class="card"><div class="card-body"><form method="POST" action="{{ $pembayaran->exists ? route('pembayarans.update', $pembayaran) : route('pembayarans.store') }}" enctype="multipart/form-data">
@csrf @if($pembayaran->exists) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Data Sewa</label><select name="sewa_id" class="form-select" required><option value="">Pilih data sewa</option>@foreach($sewas as $sewa)<option value="{{ $sewa->id }}" @selected((string) old('sewa_id', $pembayaran->sewa_id) === (string) $sewa->id)>{{ $sewa->penghuni->nama ?? '-' }} - Kamar {{ $sewa->kamar->nomor ?? '-' }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Periode</label><input type="date" name="periode" class="form-control" value="{{ old('periode', optional($pembayaran->periode)->format('Y-m-d')) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Tanggal Bayar</label><input type="date" name="tanggal_bayar" class="form-control" value="{{ old('tanggal_bayar', optional($pembayaran->tanggal_bayar)->format('Y-m-d')) }}"></div><div class="col-md-4 mb-3"><label class="form-label">Metode</label><select name="metode" class="form-select" required>@foreach(['cash','transfer','e-wallet'] as $metode)<option value="{{ $metode }}" @selected(old('metode', $pembayaran->metode ?? 'transfer') === $metode)>{{ ucfirst($metode) }}</option>@endforeach</select></div></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Jumlah</label><input type="number" min="0" step="0.01" name="jumlah" class="form-control" value="{{ old('jumlah', $pembayaran->jumlah) }}" required></div><div class="col-md-6 mb-3"><label class="form-label">Status Approval</label><input type="text" class="form-control" value="{{ $pembayaran->exists ? ($pembayaran->status === 'lunas' ? 'Disetujui Pemilik' : 'Menunggu Approval Pemilik') : 'Menunggu Approval Pemilik' }}" readonly><small class="text-body-secondary d-block mt-1">Pembayaran hanya dicatat sebagai lunas setelah pemilik menekan tombol Approve.</small></div></div>
<div class="mb-3"><label class="form-label">Bukti Pembayaran</label><input type="file" name="bukti_pembayaran" class="form-control" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf">@if($pembayaran->bukti_pembayaran_path)<small class="text-body-secondary d-block mt-1">Sudah ada file tersimpan.</small>@endif</div>
<div class="mb-3"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $pembayaran->keterangan) }}</textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Simpan</button><a href="{{ route('pembayarans.index') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection
