@php($pageTitle = 'Detail Kamar')
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-door-open"></i></div>
    <div><h2>Detail Kamar {{ $kamar->nomor }}</h2><p>Spesifikasi, harga, status, dan ringkasan histori kamar.</p></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0">Informasi Kamar</h5>
        <div class="d-flex flex-wrap gap-2">
            @canMenu('master_data.data_kamar', 'update')
            <a href="{{ route('kamars.edit', $kamar) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit me-1"></i>Edit</a>
            @endCanMenu
            @if(($kamar->sewas_count + $kamar->reservasis_count) === 0)
                @canMenu('master_data.data_kamar', 'delete')
                <form method="POST" action="{{ route('kamars.destroy', $kamar) }}" onsubmit="return confirm('Hapus kamar ini?')" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Hapus</button>
                </form>
                @endCanMenu
            @else
                <span class="badge bg-label-secondary">Riwayat sewa/reservasi tersimpan</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <dl class="row gy-3 mb-0">
            <dt class="col-sm-4 text-body-secondary">Nomor / tipe</dt><dd class="col-sm-8 mb-0">{{ $kamar->nomor }} / {{ $kamar->tipe }}</dd>
            <dt class="col-sm-4 text-body-secondary">Harga bulanan</dt><dd class="col-sm-8 mb-0">Rp {{ number_format((float) $kamar->harga_bulanan, 0, ',', '.') }}</dd>
            <dt class="col-sm-4 text-body-secondary">Status</dt><dd class="col-sm-8 mb-0"><span class="badge bg-label-{{ $kamar->status === 'tersedia' ? 'success' : ($kamar->status === 'terisi' ? 'danger' : 'secondary') }}">{{ ucfirst($kamar->status) }}</span></dd>
            <dt class="col-sm-4 text-body-secondary">Posisi layout</dt><dd class="col-sm-8 mb-0">Lantai {{ $kamar->layout_floor ?? '-' }}, baris {{ $kamar->layout_row ?? '-' }}, kolom {{ $kamar->layout_col ?? '-' }}</dd>
            <dt class="col-sm-4 text-body-secondary">Histori</dt><dd class="col-sm-8 mb-0">{{ $kamar->sewas_count }} sewa / {{ $kamar->reservasis_count }} reservasi</dd>
            <dt class="col-sm-4 text-body-secondary">Deskripsi</dt><dd class="col-sm-8 mb-0">{{ $kamar->deskripsi ?: '-' }}</dd>
        </dl>
    </div>
</div>

<a href="{{ route('kamars.index') }}" class="btn btn-outline-secondary mt-4"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
@endsection
