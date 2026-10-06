@php($pageTitle = 'Profil Kost')
@php($pageSubtitle = 'Pengaturan informasi identitas usaha kost.')
@extends('layouts.app')

@section('content')
<div class="page-head"><h2>Profil Kost</h2><p>Perbarui informasi utama kost kamu.</p></div>

<div class="card"><div class="card-body"><form method="POST" action="{{ route('profil-kost.update') }}">
@csrf @method('PUT')
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Nama Kost</label><input type="text" name="nama_kost" class="form-control" value="{{ old('nama_kost', $profile->nama_kost) }}" required></div><div class="col-md-6 mb-3"><label class="form-label">Telepon</label><input type="text" name="telepon" class="form-control" value="{{ old('telepon', $profile->telepon) }}"></div></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $profile->email) }}"></div><div class="col-md-6 mb-3"><label class="form-label">Pemilik</label><input type="text" name="pemilik" class="form-control" value="{{ old('pemilik', $profile->pemilik) }}"></div></div>
<div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="3">{{ old('alamat', $profile->alamat) }}</textarea></div>
<div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="4">{{ old('deskripsi', $profile->deskripsi) }}</textarea></div>
<hr class="my-4">
<h5 class="mb-3">Pengaturan Diskon Masa Sewa</h5>
<p class="text-body-secondary" style="margin-top:-6px;">Nominal diskon akan dipotong dari total tagihan sesuai masa sewa.</p>
<div class="row"><div class="col-md-3 mb-3"><label class="form-label">Diskon 1 Bulan (Rp)</label><input type="number" name="diskon_sewa_1_bulan" min="0" step="1000" class="form-control" value="{{ old('diskon_sewa_1_bulan', (float) ($profile->diskon_sewa_1_bulan ?? 0)) }}"></div><div class="col-md-3 mb-3"><label class="form-label">Diskon 3 Bulan (Rp)</label><input type="number" name="diskon_sewa_3_bulan" min="0" step="1000" class="form-control" value="{{ old('diskon_sewa_3_bulan', (float) ($profile->diskon_sewa_3_bulan ?? 0)) }}"></div><div class="col-md-3 mb-3"><label class="form-label">Diskon 6 Bulan (Rp)</label><input type="number" name="diskon_sewa_6_bulan" min="0" step="1000" class="form-control" value="{{ old('diskon_sewa_6_bulan', (float) ($profile->diskon_sewa_6_bulan ?? 0)) }}"></div><div class="col-md-3 mb-3"><label class="form-label">Diskon 12 Bulan (Rp)</label><input type="number" name="diskon_sewa_12_bulan" min="0" step="1000" class="form-control" value="{{ old('diskon_sewa_12_bulan', (float) ($profile->diskon_sewa_12_bulan ?? 0)) }}"></div></div>
<hr class="my-4">
<h5 class="mb-3">Pengaturan Notifikasi WhatsApp</h5>
<div class="mb-3 form-check"><input type="hidden" name="whatsapp_enabled" value="0"><input class="form-check-input" type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $profile->whatsapp_enabled))><label class="form-check-label" for="whatsapp_enabled">Aktifkan notifikasi WhatsApp</label></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Provider</label><select name="whatsapp_provider" class="form-select"><option value="fonnte" @selected(old('whatsapp_provider', $profile->whatsapp_provider ?? 'fonnte') === 'fonnte')>Fonnte</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Timeout (detik)</label><input type="number" name="whatsapp_timeout" min="3" max="60" class="form-control" value="{{ old('whatsapp_timeout', $profile->whatsapp_timeout ?? 10) }}"></div></div>
<div class="mb-3"><label class="form-label">Base URL API</label><input type="url" name="whatsapp_base_url" class="form-control" value="{{ old('whatsapp_base_url', $profile->whatsapp_base_url ?? 'https://api.fonnte.com') }}" placeholder="https://api.fonnte.com"></div>
<div class="row"><div class="col-md-8 mb-3"><label class="form-label">Token API WhatsApp</label><input type="text" name="whatsapp_token" class="form-control" value="{{ old('whatsapp_token', $profile->whatsapp_token) }}" placeholder="Masukkan token API"></div><div class="col-md-4 mb-3"><label class="form-label">Kode Negara Default</label><input type="text" name="whatsapp_default_country_code" class="form-control" value="{{ old('whatsapp_default_country_code', $profile->whatsapp_default_country_code ?? '62') }}" placeholder="62"></div></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Simpan Profil</button><a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection
