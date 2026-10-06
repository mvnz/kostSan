@php
$pageTitle = 'Profil Kost';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-buildings"></i></div>
    <div>
        <h2>Profil Kost</h2>
        <p>Kelola identitas, kontak, dan deskripsi kost.</p>
    </div>
</div>

@include('profil-kost._submenu')

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('profil-kost.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="form-section">
                <div class="form-section-title"><i class="bx bx-buildings"></i> Identitas Kost</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Kost</label>
                        <input type="text" name="nama_kost" class="form-control" value="{{ old('nama_kost', $profile->nama_kost) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pemilik</label>
                        <input type="text" name="pemilik" class="form-control" value="{{ old('pemilik', $profile->pemilik) }}" placeholder="Nama pemilik kost">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="bx bx-phone"></i> Kontak</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telepon</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-phone"></i></span>
                            <input type="text" name="telepon" class="form-control" value="{{ old('telepon', $profile->telepon) }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $profile->email) }}" placeholder="email@kost.com">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="bx bx-map-pin"></i> Lokasi & Deskripsi</div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap kost">{{ old('alamat', $profile->alamat) }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi singkat tentang kost Anda">{{ old('deskripsi', $profile->deskripsi) }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Profil</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
