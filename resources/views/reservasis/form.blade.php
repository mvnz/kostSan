@php
$pageTitle = $reservasi->exists ? 'Edit Reservasi' : 'Tambah Reservasi';
$pageSubtitle = 'Form transaksi reservasi kamar.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-calendar-check"></i></div>
    <div>
        <h2>{{ $reservasi->exists ? 'Edit Reservasi' : 'Tambah Reservasi' }}</h2>
        <p>Isi data calon penghuni dan rencana masuk.</p>
    </div>
</div>
<div class="card"><div class="card-body"><form method="POST" action="{{ $reservasi->exists ? route('reservasis.update', $reservasi) : route('reservasis.store') }}">
@csrf @if($reservasi->exists) @method('PUT') @endif
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Kamar</label><select name="kamar_id" class="form-select" required><option value="">Pilih kamar</option>@foreach($kamars as $kamar)<option value="{{ $kamar->id }}" @selected((string) old('kamar_id', $reservasi->kamar_id) === (string) $kamar->id)>{{ $kamar->nomor }} - {{ $kamar->tipe }}</option>@endforeach</select></div><div class="col-md-6 mb-3"><label class="form-label">Penghuni</label><select name="penghuni_id" class="form-select" required><option value="">Pilih penghuni</option>@foreach($penghunis as $penghuni)<option value="{{ $penghuni->id }}" @selected((string) old('penghuni_id', $reservasi->penghuni_id) === (string) $penghuni->id)>{{ $penghuni->nama }}</option>@endforeach</select></div></div>
<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Tanggal Reservasi</label><input type="date" name="tanggal_reservasi" class="form-control" value="{{ old('tanggal_reservasi', optional($reservasi->tanggal_reservasi)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Rencana Masuk</label><input type="date" name="rencana_masuk" class="form-control" value="{{ old('rencana_masuk', optional($reservasi->rencana_masuk)->format('Y-m-d')) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Rencana Keluar</label><input type="date" name="rencana_keluar" class="form-control" value="{{ old('rencana_keluar', optional($reservasi->rencana_keluar)->format('Y-m-d')) }}"></div></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Uang Muka</label><input type="number" step="0.01" min="0" name="uang_muka" class="form-control" value="{{ old('uang_muka', $reservasi->uang_muka ?? 0) }}"></div><div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select" required>@foreach(['menunggu','dikonfirmasi','dibatalkan'] as $status)<option value="{{ $status }}" @selected(old('status', $reservasi->status ?? 'menunggu') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div></div>
<div class="mb-3"><label class="form-label">Catatan</label><textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $reservasi->catatan) }}</textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Simpan</button><a href="{{ route('reservasis.index') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection
