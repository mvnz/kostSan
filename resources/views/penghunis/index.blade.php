@php
$pageTitle = 'Penghuni';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-user"></i></div>
    <div>
        <h2>Penghuni</h2>
        <p>Identitas dan data penghuni aktif, kendaraan, dan kontaknya.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Data Penghuni</h5>
            <small class="text-body-secondary">Identitas penghuni aktif dan histori.</small>
        </div>
        <div class="d-flex gap-2">
            @canMenu('master_data.data_penghuni', 'create')
            <form method="POST" action="{{ route('penghuni-registrations.generate') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary"><i class="bx bx-link-alt me-1"></i>Generate Link Pendaftaran</button>
            </form>
            @endCanMenu
            <a href="{{ route('penghunis.cetak') }}" target="_blank" class="btn btn-outline-secondary"><i class="bx bx-printer me-1"></i>Cetak</a>
            @canMenu('master_data.data_penghuni', 'create')
            <a href="{{ route('penghunis.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Penghuni</a>
            @endCanMenu
        </div>
    </div>

    @if(session('registration_link'))
        <div class="px-4 pt-3">
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
                <div>
                    <strong>Link pendaftaran baru:</strong>
                    <div><a href="{{ session('registration_link') }}" target="_blank">{{ session('registration_link') }}</a></div>
                    <small class="text-body-secondary">Link ini otomatis tidak berlaku setelah form dikirim.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-copy-link="{{ session('registration_link') }}">
                    <i class="bx bx-copy me-1"></i>Copy
                </button>
            </div>
        </div>
    @endif

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-penghuni" data-mobile-cols="2,6">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-penghuni"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Penghuni</th>
                    <th>Kontak Utama</th>
                    <th>Kontak Darurat</th>
                    <th>Kamar & Kendaraan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                @foreach($penghunis as $penghuni)
                @php
                    $parts = explode(' ', trim($penghuni->nama));
                    $inisial = strtoupper(mb_substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(mb_substr($parts[1], 0, 1)) : '');
                @endphp
                <tr>
                    <td><div class="form-check"><input type="checkbox" class="form-check-input"></div></td>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded-circle {{ $avatarColors[$loop->index % 5] }}">{{ $inisial }}</span>
                            </div>
                            <div>
                                <strong>{{ $penghuni->nama }}</strong>
                                <small class="d-block text-body-secondary">{{ $penghuni->pekerjaan ?: 'Penghuni' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $penghuni->telepon }}</div>
                        <small class="text-body-secondary">{{ $penghuni->email ?: '-' }}</small>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $penghuni->kontak_darurat_nama ?: '-' }}</div>
                        <small class="text-body-secondary">
                            {{ $penghuni->kontak_darurat_hubungan ?: '-' }}
                            @if($penghuni->kontak_darurat_telepon)
                                • {{ $penghuni->kontak_darurat_telepon }}
                            @endif
                        </small>
                    </td>
                    <td>
                        <div class="small fw-semibold">Kamar: {{ $penghuni->nomor_kamar ?: '-' }}</div>
                        <small class="d-block text-body-secondary">
                            Kendaraan:
                            @if(($penghuni->jenis_kendaraan ?? null) === 'motor')
                                Motor{{ $penghuni->kendaraan_nomor_polisi ? ' • ' . $penghuni->kendaraan_nomor_polisi : '' }}
                            @else
                                Tidak ada
                            @endif
                        </small>
                        <small class="d-block text-body-secondary">
                            Sewa: {{ $penghuni->harga_sewa ? 'Rp ' . number_format((float) $penghuni->harga_sewa, 0, ',', '.') : '-' }}
                        </small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <a href="{{ route('penghunis.show', $penghuni) }}" class="btn btn-sm btn-action-detail">
                                <i class="icon-base bx bx-detail me-1"></i>Detail
                            </a>
                            @canMenu('master_data.data_penghuni', 'update')
                            <a href="{{ route('penghunis.edit', $penghuni) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endCanMenu
                            @canMenu('master_data.data_penghuni', 'delete')
                            <form method="POST" action="{{ route('penghunis.destroy', $penghuni) }}" onsubmit="return confirm('Hapus penghuni ini?')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                            </form>
                            @endCanMenu
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function copyLink(buttonEl) {
        const url = buttonEl.getAttribute('data-copy-link');
        if (!url) return;

        try {
            await navigator.clipboard.writeText(url);
            const oldHtml = buttonEl.innerHTML;
            buttonEl.innerHTML = '<i class="bx bx-check me-1"></i>Tersalin';
            buttonEl.classList.remove('btn-primary');
            buttonEl.classList.add('btn-success');
            setTimeout(() => {
                buttonEl.innerHTML = oldHtml;
                buttonEl.classList.remove('btn-success');
                buttonEl.classList.add('btn-primary');
            }, 1600);
        } catch (e) {
            alert('Gagal menyalin link. Silakan salin manual.');
        }
    }

    document.querySelectorAll('[data-copy-link]').forEach((btn) => {
        btn.addEventListener('click', function () {
            copyLink(this);
        });
    });
</script>
@endsection

