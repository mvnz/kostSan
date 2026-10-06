@php
$pageTitle = 'Laporan Keuangan';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-bar-chart-alt-2"></i></div>
    <div>
        <h2>Laporan Keuangan</h2>
        <p>Rekap pemasukan dan pengeluaran berdasarkan periode bulan.</p>
    </div>
</div>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="mb-0">Laporan Keuangan</h5>
        <small class="text-body-secondary">Rekap pemasukan dan pengeluaran berdasarkan periode.</small>
    </div>
    @canMenu('keuangan.laporan_keuangan', 'view')
    <a href="{{ route('laporan-keuangan.pdf', ['bulan' => $bulan]) }}" target="_blank" class="btn btn-danger btn-sm">
        <i class="bx bx-file-pdf me-1"></i> Ekspor PDF
    </a>
    @endCanMenu
</div>

<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="font-size:13px;font-weight:600;">Filter Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('laporan-keuangan.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Total Pemasukan</small>
                <div style="font-size:20px;font-weight:800;color:#20c997;margin-top:4px;">Rp {{ number_format((float)$totalPemasukan,0,',','.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Total Pengeluaran</small>
                <div style="font-size:20px;font-weight:800;color:#e53e3e;margin-top:4px;">Rp {{ number_format((float)$totalPengeluaran,0,',','.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Saldo</small>
                <div style="font-size:20px;font-weight:800;color:#4680ff;margin-top:4px;">Rp {{ number_format((float)$saldo,0,',','.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table datatable" id="tbl-laporan" data-mobile-cols="1,2,4">
                <thead>
                    <tr>
                        <th data-orderable="false">#</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ optional($item->tanggal)->format('d/m/Y') }}</td>
                        <td>
                            @if($item->jenis === 'pemasukan')
                                <span class="badge rounded-pill bg-label-success">Pemasukan</span>
                            @else
                                <span class="badge rounded-pill bg-label-danger">Pengeluaran</span>
                            @endif
                        </td>
                        <td>{{ $item->kategori }}</td>
                        <td>Rp {{ number_format((float)$item->jumlah,0,',','.') }}</td>
                        <td>{{ $item->deskripsi ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
