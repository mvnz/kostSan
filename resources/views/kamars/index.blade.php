@php
$pageTitle = 'Kamar';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-door-open"></i></div>
    <div>
        <h2>Kamar</h2>
        <p>Master data kamar, tipe, harga, lantai, dan status hunian.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Master Data Kamar</h5>
            <small class="text-body-secondary">Nomor, lantai, tipe, harga, dan status kamar.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <select id="filter-floor" class="form-select" style="min-width: 210px;">
                <option value="">Semua Lantai</option>
                @foreach($floorsByNumber as $floorNumber => $floorName)
                    <option value="{{ $floorNumber }}">{{ $floorName }} ({{ $floorNumber }})</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export</button>
            @canMenu('master_data.data_kamar', 'create')
            <a href="{{ route('kamars.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Kamar</a>
            @endCanMenu
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-kamar" data-mobile-cols="2,4,6,7">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-kamar"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Nomor</th>
                    <th>Tipe</th>
                    <th>Lantai</th>
                    <th>Harga / Bulan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kamars as $kamar)
                <tr>
                    <td><div class="form-check"><input type="checkbox" class="form-check-input"></div></td>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-initial rounded bg-label-primary px-2 py-1 fw-bold">{{ $kamar->nomor }}</span>
                        </div>
                    </td>
                    <td>{{ $kamar->tipe }}</td>
                    <td>
                        @php($floorName = $floorsByNumber[(int) $kamar->layout_floor] ?? null)
                        @if($kamar->layout_floor)
                            <span class="d-none">FLOOR:{{ (int) $kamar->layout_floor }}</span>
                            <span class="badge bg-label-info">{{ $floorName ?: 'Lantai ' . $kamar->layout_floor }} ({{ $kamar->layout_floor }})</span>
                        @else
                            <span class="text-body-secondary">-</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format((float)$kamar->harga_bulanan, 0, ',', '.') }}</td>
                    <td>
                        @if($kamar->status === 'tersedia')
                            <span class="badge rounded-pill bg-label-success">Tersedia</span>
                        @elseif($kamar->status === 'terisi')
                            <span class="badge rounded-pill bg-label-danger">Terisi</span>
                        @elseif($kamar->status === 'reservasi')
                            <span class="badge rounded-pill bg-label-warning">Reservasi</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary">Perbaikan</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <a href="{{ route('kamars.show', $kamar) }}" class="btn btn-sm btn-action-detail">
                                <i class="icon-base bx bx-detail me-1"></i>Detail
                            </a>
                            @canMenu('master_data.data_kamar', 'update')
                            <a href="{{ route('kamars.edit', $kamar) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endCanMenu
                            @canMenu('master_data.data_kamar', 'delete')
                            <form method="POST" action="{{ route('kamars.destroy', $kamar) }}" onsubmit="return confirm('Hapus kamar ini?')" class="d-inline">
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
    $(function () {
        const table = $('#tbl-kamar').DataTable();
        const floorColumnIndex = 4;

        $('#filter-floor').on('change', function () {
            const floor = $(this).val();
            const query = floor ? 'FLOOR:' + floor : '';
            table.column(floorColumnIndex).search(query, false, false).draw();
        });
    });
</script>
@endsection
