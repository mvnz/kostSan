@php
$pageTitle = $kamar->exists ? 'Edit Kamar' : 'Tambah Kamar';
$pageSubtitle = 'Form data kamar kost.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-door-open"></i></div>
    <div>
        <h2>{{ $kamar->exists ? 'Edit Kamar' : 'Tambah Kamar' }}</h2>
        <p>Lengkapi data kamar sebelum disimpan.</p>
    </div>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ $kamar->exists ? route('kamars.update', $kamar) : route('kamars.store') }}">
@csrf @if($kamar->exists) @method('PUT') @endif
@if($tipeHargas->isEmpty())
<div class="alert alert-warning" role="alert">
    Belum ada data Tipe Kamar. Tambahkan tipe kamar dan harganya dulu di menu Pengaturan &gt; Tipe Kamar.
</div>
@endif
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Nomor Kamar</label><input type="text" name="nomor" class="form-control" value="{{ old('nomor', $kamar->nomor) }}" required></div>
<div class="col-md-6 mb-3">
    <label class="form-label">Tipe Kamar</label>
    <select name="tipe" id="input-tipe-kamar" class="form-select" required>
        <option value="">-- Pilih Tipe Kamar --</option>
        @foreach($tipeHargas as $tipeHarga)
        <option value="{{ $tipeHarga->tipe }}" data-harga-1-bulan="{{ (float) $tipeHarga->harga_1_bulan }}" @selected(old('tipe', $kamar->tipe) === $tipeHarga->tipe)>{{ $tipeHarga->tipe }}</option>
        @endforeach
    </select>
</div>
</div>
<div class="row">
<div class="col-md-6 mb-3">
    <label class="form-label">Harga Bulanan</label>
    <input type="text" id="display-harga-bulanan" class="form-control" value="Rp {{ number_format(old('tipe', $kamar->tipe) ? $kamar->harga_bulanan : 0, 0, ',', '.') }}" readonly>
    <small class="text-body-secondary">Mengikuti harga 1 bulan dari Pengaturan Tipe Kamar.</small>
</div>
<div class="col-md-6 mb-3"><label class="form-label">Nomor Lantai</label><select name="layout_floor" class="form-select" required>@foreach($floors as $floor)<option value="{{ $floor->number }}" @selected((int) old('layout_floor', $kamar->layout_floor ?: 1) === (int) $floor->number)>{{ $floor->name }} ({{ $floor->number }})</option>@endforeach</select></div>
</div>

<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select" required>@foreach(['tersedia','terisi','perbaikan'] as $status)<option value="{{ $status }}" @selected(old('status', $kamar->status ?? 'tersedia') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div>
<div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $kamar->deskripsi) }}</textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Simpan</button><a href="{{ route('kamars.index') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection

@section('scripts')
<script>
    (function () {
        const tipeSelect = document.getElementById('input-tipe-kamar');
        const hargaDisplay = document.getElementById('display-harga-bulanan');

        function syncHarga() {
            const option = tipeSelect.options[tipeSelect.selectedIndex];
            const harga = option ? parseFloat(option.getAttribute('data-harga-1-bulan') || '0') : 0;
            hargaDisplay.value = 'Rp ' + harga.toLocaleString('id-ID');
        }

        tipeSelect?.addEventListener('change', syncHarga);
    })();
</script>
@endsection
