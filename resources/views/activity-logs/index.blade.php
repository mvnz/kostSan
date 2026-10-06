@php
$pageTitle = 'Log Aktivitas';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-history"></i></div>
    <div>
        <h2>Log Aktivitas</h2>
        <p>Riwayat aktivitas sistem: login, pembayaran, dan seluruh perubahan data (berhasil maupun gagal).</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option>
                    <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Modul</label>
                <select name="module" class="form-select">
                    <option value="">Semua</option>
                    @foreach($modules as $moduleOption)
                        <option value="{{ $moduleOption }}" @selected(request('module') === $moduleOption)>{{ ucfirst(str_replace('-', ' ', $moduleOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="dari_tanggal" class="form-control" value="{{ request('dari_tanggal') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" class="form-control" value="{{ request('sampai_tanggal') }}">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i>Filter</button>
            </div>
            <div class="col-12">
                <label class="form-label">Pencarian (nama pengguna, deskripsi, route, IP)</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Contoh: admin@kost.com, kamars.store, 127.0.0.1">
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Riwayat Aktivitas</h5>
        <small class="text-body-secondary">Menampilkan maksimal 1000 aktivitas terbaru sesuai filter.</small>
    </div>

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-activity-logs" data-mobile-cols="1,2,3,6">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Modul</th>
                    <th>Aksi</th>
                    <th>Status</th>
                    <th>Deskripsi</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user_name ?: 'Publik' }}</td>
                    <td>{{ ucfirst(str_replace('-', ' ', $log->module)) }}</td>
                    <td>{{ $log->action }}</td>
                    <td>
                        @if($log->status === 'berhasil')
                            <span class="badge bg-label-success">Berhasil</span>
                        @else
                            <span class="badge bg-label-danger">Gagal</span>
                        @endif
                    </td>
                    <td>{{ $log->description ?: '-' }}</td>
                    <td>{{ $log->ip_address ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
