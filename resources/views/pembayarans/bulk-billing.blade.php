@php
$pageTitle = 'Bulk Billing';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-receipt"></i></div>
    <div>
        <h2>Bulk Billing</h2>
        <p>Buat tagihan serentak untuk semua penghuni aktif dalam satu periode.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="font-size:13px;font-weight:600;">Pilih Periode</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

@php
    $belumAda  = $sewas->filter(fn($s) => !$s['sudah_ada']);
    $sudahAda  = $sewas->filter(fn($s) =>  $s['sudah_ada']);
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Total Penghuni Aktif</small>
                <div style="font-size:20px;font-weight:800;color:#4680ff;margin-top:4px;">{{ $sewas->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Belum Ada Tagihan</small>
                <div style="font-size:20px;font-weight:800;color:#e8531d;margin-top:4px;">{{ $belumAda->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body p-3">
                <small style="color:#9aa5b8;font-weight:600;font-size:12px;">Sudah Ada Tagihan</small>
                <div style="font-size:20px;font-weight:800;color:#20c997;margin-top:4px;">{{ $sudahAda->count() }}</div>
            </div>
        </div>
    </div>
</div>

@if($belumAda->count() > 0)
<form method="POST" action="{{ route('pembayarans.bulk-billing.store') }}">
    @csrf
    <input type="hidden" name="bulan" value="{{ $bulan }}">
    <div class="card mb-3">
        <div class="card-header p-3 d-flex align-items-center justify-content-between">
            <strong>Tagihan akan dibuat ({{ $belumAda->count() }} penghuni)</strong>
            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Buat {{ $belumAda->count() }} tagihan untuk periode {{ $periode->translatedFormat('F Y') }}?')">
                <i class="bx bx-plus-circle me-1"></i> Buat Semua Tagihan
            </button>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th class="text-end">Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($belumAda as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['sewa']->penghuni?->nama ?? '-' }}</td>
                            <td>{{ $row['sewa']->kamar?->nomor ?? '-' }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float)$row['sewa']->biaya_bulanan, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($belumAda->sum(fn($r) => (float)$r['sewa']->biaya_bulanan), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</form>
@else
<div class="alert alert-success">
    <i class="bx bx-check-circle me-1"></i> Semua penghuni aktif sudah memiliki tagihan untuk periode <strong>{{ $periode->translatedFormat('F Y') }}</strong>.
</div>
@endif

@if($sudahAda->count() > 0)
<div class="card">
    <div class="card-header p-3">
        <strong class="text-muted">Sudah Ada Tagihan ({{ $sudahAda->count() }} penghuni)</strong>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penghuni</th>
                        <th>Kamar</th>
                        <th class="text-end">Tagihan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sudahAda as $i => $row)
                    @php $p = $row['sewa']->pembayarans->where('periode', $row['sewa']->pembayarans()->whereYear('periode', $periode->year)->whereMonth('periode', $periode->month)->first()?->periode)->first(); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row['sewa']->penghuni?->nama ?? '-' }}</td>
                        <td>{{ $row['sewa']->kamar?->nomor ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float)$row['sewa']->biaya_bulanan, 0, ',', '.') }}</td>
                        <td><span class="badge bg-label-success" style="font-size:11px;">Sudah Ada</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
