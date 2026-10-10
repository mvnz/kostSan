@php
$pageTitle = 'Reservasi';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-calendar-check"></i></div>
    <div>
        <h2>Reservasi</h2>
        <p>Reservasi kamar sebelum menjadi sewa aktif.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Reservasi Kamar</h5>
            <small class="text-body-secondary">Reservasi sebelum menjadi sewa aktif.</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export</button>
            @canMenu('manajemen_sewa.data_sewa', 'create')
            <a href="{{ route('reservasis.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Reservasi</a>
            @endCanMenu
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-reservasi" data-mobile-cols="2,5,6,7">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-reservasi"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Penghuni</th>
                    <th>Tanggal Reservasi</th>
                    <th>Rencana Masuk</th>
                    <th>Uang Muka</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                @foreach($reservasis as $reservasi)
                @php
                    $nama = $reservasi->penghuni->nama ?? 'Unknown';
                    $parts = explode(' ', trim($nama));
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
                                <strong>{{ $nama }}</strong>
                                <small class="d-block text-body-secondary">Kamar {{ $reservasi->kamar->nomor ?? '-' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ optional($reservasi->tanggal_reservasi)->format('d/m/Y') }}</td>
                    <td>{{ optional($reservasi->rencana_masuk)->format('d/m/Y') }}</td>
                    <td>Rp {{ number_format((float)$reservasi->uang_muka,0,',','.') }}</td>
                    <td>
                        @if($reservasi->status === 'menunggu')
                            <span class="badge rounded-pill bg-label-warning">Menunggu</span>
                        @elseif($reservasi->status === 'dikonfirmasi')
                            <span class="badge rounded-pill bg-label-success">Dikonfirmasi</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary">{{ ucfirst($reservasi->status) }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <a href="{{ route('reservasis.show', $reservasi) }}" class="btn btn-sm btn-action-detail">
                                <i class="icon-base bx bx-detail me-1"></i>Detail
                            </a>
                            @canMenu('manajemen_sewa.data_sewa', 'create')
                            @if($reservasi->status === 'dikonfirmasi' && $reservasi->sewa_id === null)
                            <a href="{{ route('sewas.create', ['reservasi_id' => $reservasi->id]) }}" class="btn btn-sm btn-success">
                                <i class="icon-base bx bx-transfer me-1"></i>Jadikan Sewa
                            </a>
                            @endif
                            @endCanMenu
                            @canMenu('manajemen_sewa.data_sewa', 'update')
                            @if($reservasi->sewa_id === null)
                            <a href="{{ route('reservasis.edit', $reservasi) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endif
                            @endCanMenu
                            @canMenu('manajemen_sewa.data_sewa', 'delete')
                            @if($reservasi->sewa_id === null)
                            <form method="POST" action="{{ route('reservasis.destroy', $reservasi) }}" onsubmit="return confirm('Hapus reservasi ini?')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                            </form>
                            @endif
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

