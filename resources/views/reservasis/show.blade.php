@php($pageTitle = 'Detail Reservasi')
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-calendar-check"></i></div>
    <div><h2>Detail Reservasi</h2><p>Rencana hunian, uang muka, dan status reservasi.</p></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0">Reservasi #{{ $reservasi->id }}</h5>
        <div class="d-flex flex-wrap gap-2">
            @canMenu('manajemen_sewa.data_sewa', 'update')
            <a href="{{ route('reservasis.edit', $reservasi) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit me-1"></i>Edit</a>
            @endCanMenu
            @canMenu('manajemen_sewa.data_sewa', 'delete')
            <form method="POST" action="{{ route('reservasis.destroy', $reservasi) }}" onsubmit="return confirm('Hapus reservasi ini?')" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bx bx-trash me-1"></i>Hapus</button>
            </form>
            @endCanMenu
        </div>
    </div>
    <div class="card-body">
        <dl class="row gy-3 mb-0">
            <dt class="col-sm-4 text-body-secondary">Penghuni</dt><dd class="col-sm-8 mb-0">{{ $reservasi->penghuni?->nama ?? '-' }}</dd>
            <dt class="col-sm-4 text-body-secondary">Kamar</dt><dd class="col-sm-8 mb-0">{{ $reservasi->kamar?->nomor ?? '-' }} / {{ $reservasi->kamar?->tipe ?? '-' }}</dd>
            <dt class="col-sm-4 text-body-secondary">Tanggal reservasi</dt><dd class="col-sm-8 mb-0">{{ $reservasi->tanggal_reservasi?->format('d/m/Y') ?? '-' }}</dd>
            <dt class="col-sm-4 text-body-secondary">Rencana tinggal</dt><dd class="col-sm-8 mb-0">{{ $reservasi->rencana_masuk?->format('d/m/Y') ?? '-' }} – {{ $reservasi->rencana_keluar?->format('d/m/Y') ?? 'belum ditentukan' }}</dd>
            <dt class="col-sm-4 text-body-secondary">Uang muka</dt><dd class="col-sm-8 mb-0">Rp {{ number_format((float) $reservasi->uang_muka, 0, ',', '.') }}</dd>
            <dt class="col-sm-4 text-body-secondary">Status</dt><dd class="col-sm-8 mb-0"><span class="badge bg-label-{{ $reservasi->status === 'dikonfirmasi' ? 'success' : ($reservasi->status === 'menunggu' ? 'warning' : 'secondary') }}">{{ ucfirst($reservasi->status) }}</span></dd>
            <dt class="col-sm-4 text-body-secondary">Catatan</dt><dd class="col-sm-8 mb-0">{{ $reservasi->catatan ?: '-' }}</dd>
        </dl>
    </div>
</div>

<a href="{{ route('reservasis.index') }}" class="btn btn-outline-secondary mt-4"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
@endsection
