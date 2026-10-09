@php($pageTitle = 'Detail Penghuni')
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-user"></i></div>
    <div>
        <h2>Detail Penghuni</h2>
        <p>Data identitas, kontak, dokumen, dan ringkasan relasi operasional.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="mb-0">{{ $penghuni->nama }}</h5>
                <div class="d-flex flex-wrap gap-2">
                    @canMenu('master_data.data_penghuni', 'update')
                    <a href="{{ route('penghunis.edit', $penghuni) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit me-1"></i>Edit</a>
                    @endCanMenu
                    @canMenu('master_data.data_penghuni', 'delete')
                    <form method="POST" action="{{ route('penghunis.destroy', $penghuni) }}" onsubmit="return confirm('Hapus penghuni ini?')" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Hapus</button>
                    </form>
                    @endCanMenu
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0 gy-3">
                    <dt class="col-sm-4 text-body-secondary">Kamar / kunci</dt><dd class="col-sm-8 mb-0">{{ $penghuni->nomor_kamar ?: '-' }} / {{ $penghuni->jumlah_kunci ?? 0 }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Tempat, tanggal lahir</dt><dd class="col-sm-8 mb-0">{{ $penghuni->tempat_lahir ?: '-' }}{{ $penghuni->tanggal_lahir ? ', '.$penghuni->tanggal_lahir->format('d/m/Y') : '' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">NIK</dt><dd class="col-sm-8 mb-0 text-break">{{ $penghuni->nik ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Telepon / email</dt><dd class="col-sm-8 mb-0 text-break">{{ $penghuni->telepon ?: '-' }} / {{ $penghuni->email ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Alamat KTP</dt><dd class="col-sm-8 mb-0">{{ $penghuni->alamat_ktp ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Alamat domisili</dt><dd class="col-sm-8 mb-0">{{ $penghuni->alamat ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Pekerjaan</dt><dd class="col-sm-8 mb-0">{{ $penghuni->pekerjaan ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Kontak darurat</dt><dd class="col-sm-8 mb-0">{{ $penghuni->kontak_darurat_nama ?: '-' }} / {{ $penghuni->kontak_darurat_hubungan ?: '-' }} / {{ $penghuni->kontak_darurat_telepon ?: '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Kendaraan</dt><dd class="col-sm-8 mb-0">{{ $penghuni->jenis_kendaraan === 'motor' ? trim(collect([$penghuni->kendaraan_merek_tipe, $penghuni->kendaraan_warna, $penghuni->kendaraan_nomor_polisi])->filter()->join(' / ')) : 'Tidak ada' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Mulai / jatuh tempo</dt><dd class="col-sm-8 mb-0">{{ $penghuni->tanggal_mulai_tinggal?->format('d/m/Y') ?? '-' }} / {{ $penghuni->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}</dd>
                    <dt class="col-sm-4 text-body-secondary">Catatan pengelola</dt><dd class="col-sm-8 mb-0">{{ $penghuni->catatan_pengelola ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Dokumen Privat</h5></div>
            <div class="card-body d-grid gap-2">
                @if($penghuni->foto_ktp_path)
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('secure-files.show', ['path' => $penghuni->foto_ktp_path]) }}">Lihat foto KTP</a>
                @else
                    <span class="text-body-secondary">Foto KTP belum tersedia.</span>
                @endif
                @if($penghuni->foto_selfie_path)
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('secure-files.show', ['path' => $penghuni->foto_selfie_path]) }}">Lihat foto selfie</a>
                @else
                    <span class="text-body-secondary">Foto selfie belum tersedia.</span>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Ringkasan Relasi</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Sewa</span><strong>{{ $penghuni->sewas_count }}</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Reservasi</span><strong>{{ $penghuni->reservasis_count }}</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Invoice</span><strong>{{ $penghuni->invoices_count }}</strong></div>
                @if($penghuni->sewas_count || $penghuni->reservasis_count || $penghuni->invoices_count)
                    <div class="alert alert-warning small mt-3 mb-0">Data dengan relasi operasional tidak dapat dihapus.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<a href="{{ route('penghunis.index') }}" class="btn btn-outline-secondary mt-4"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
@endsection
