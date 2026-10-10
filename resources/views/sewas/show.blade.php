@php($pageTitle = 'Detail Sewa')
@extends('layouts.app')

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('dashboard') }}" class="text-muted">Home</a> /
        <a href="{{ route('sewas.index') }}" class="text-muted">Sewa</a> /
    </span>
    Detail Sewa
</h4>

<div class="row">
    {{-- Left: Main info --}}
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Informasi Sewa</h5>
                <div class="d-flex gap-2">
                    @canMenu('manajemen_sewa.data_sewa', 'update')
                    <a href="{{ route('sewas.edit', $sewa) }}" class="btn btn-sm btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    @endCanMenu
                    @if($sewa->pembayarans->isEmpty() && !$sewa->convertedReservation)
                    @canMenu('manajemen_sewa.data_sewa', 'delete')
                    <form method="POST" action="{{ route('sewas.destroy', $sewa) }}" onsubmit="return confirm('Hapus sewa ini?')" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-trash me-1"></i>Hapus
                        </button>
                    </form>
                    @endCanMenu
                    @else
                    @if($sewa->pembayarans->isNotEmpty())
                    <span class="badge bg-label-secondary" title="Sewa dengan riwayat pembayaran tidak dapat dihapus">Riwayat pembayaran tersimpan</span>
                    @else
                    <span class="badge bg-label-secondary" title="Sewa yang berasal dari reservasi tidak dapat dihapus">Reservasi asal tersimpan</span>
                    @endif
                    @endif
                </div>
            </div>
            <div class="card-body">
                {{-- Penghuni & Kamar --}}
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.1rem">{{ $inisial }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $sewa->penghuni->nama ?? 'Unknown' }}</h5>
                        <small class="text-body-secondary">{{ $sewa->penghuni->telepon ?? '-' }}</small>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="fw-bold">Kamar {{ $sewa->kamar->nomor ?? '-' }}</div>
                        <small class="text-body-secondary">{{ $sewa->kamar->tipe ?? '-' }}</small>
                    </div>
                </div>

                {{-- Detail rows --}}
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Status</small>
                        @if($sewa->status === 'aktif')
                            <span class="badge rounded-pill bg-label-success mt-1">Aktif</span>
                        @elseif($sewa->status === 'menunggak')
                            <span class="badge rounded-pill bg-label-warning mt-1">Menunggak</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary mt-1">{{ ucfirst($sewa->status) }}</span>
                        @endif
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Tanggal Masuk</small>
                        <strong>{{ optional($sewa->tanggal_masuk)->format('d/m/Y') ?? '-' }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Tanggal Keluar</small>
                        <strong>{{ optional($sewa->tanggal_keluar)->format('d/m/Y') ?? '-' }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Durasi</small>
                        <strong>
                            @if($sewa->tanggal_masuk && $sewa->tanggal_keluar)
                                {{ $bulan }} Bulan
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Biaya / Bulan</small>
                        <strong>Rp {{ number_format((float)$sewa->biaya_bulanan,0,',','.') }}</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Uang Jaminan</small>
                        <strong>Rp {{ number_format((float)$sewa->uang_jaminan,0,',','.') }}</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Total Tagihan</small>
                        <strong class="text-primary">Rp {{ number_format($total,0,',','.') }}</strong>
                    </div>
                    <div class="col-12">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Bukti Sewa</small>
                        @if($sewa->bukti_pembayaran)
                            <a href="{{ route('secure-files.show', ['path' => $sewa->bukti_pembayaran]) }}" target="_blank" rel="noopener">Lihat bukti privat</a>
                        @else
                            <span>Belum ada bukti</span>
                        @endif
                    </div>
                    @if($sewa->convertedReservation)
                    <div class="col-12">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Reservasi Asal</small>
                        <a href="{{ route('reservasis.show', $sewa->convertedReservation) }}">Reservasi #{{ $sewa->convertedReservation->id }}</a>
                    </div>
                    @endif
                    @if($sewa->catatan)
                    <div class="col-12">
                        <small class="text-body-secondary d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Catatan</small>
                        <span>{{ $sewa->catatan }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Ringkasan pembayaran --}}
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Riwayat Pembayaran</h5>
            </div>
            <div class="card-body p-0">
                @forelse($sewa->pembayarans->sortByDesc('periode') as $bayar)
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <div>
                        <div class="fw-semibold" style="font-size:.85rem">{{ optional($bayar->periode)->format('m/Y') }}</div>
                        <small class="text-body-secondary">Rp {{ number_format((float)$bayar->jumlah,0,',','.') }}</small>
                    </div>
                    @if($bayar->status === 'lunas')
                        <span class="badge rounded-pill bg-label-success">Lunas</span>
                    @else
                        <span class="badge rounded-pill bg-label-warning">Belum Lunas</span>
                    @endif
                </div>
                @empty
                <div class="px-3 py-4 text-center text-body-secondary">
                    <i class="bx bx-receipt d-block mb-1" style="font-size:2rem"></i>
                    Belum ada pembayaran
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<a href="{{ route('sewas.index') }}" class="btn btn-outline-secondary">
    <i class="bx bx-arrow-back me-1"></i>Kembali
</a>
@endsection
