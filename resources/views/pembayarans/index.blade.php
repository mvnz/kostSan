@extends('layouts.app')

@section('content')
@php
    $totalTransaksi = $pembayarans->count();
    $totalLunas = $pembayarans->where('status', 'lunas')->count();
    $totalPending = $pembayarans->where('status', 'belum_lunas')->count();
    $totalNominal = (float) $pembayarans->sum('jumlah');
    $nominalLunas = (float) $pembayarans->where('status', 'lunas')->sum('jumlah');
    $nominalPending = (float) $pembayarans->where('status', 'belum_lunas')->sum('jumlah');
@endphp

<style>
    .payment-stat-card {
        border: 1px solid #e7ecf6;
        border-radius: .9rem;
        background: #fff;
        padding: .95rem 1rem;
        height: 100%;
    }

    .payment-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: .7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .payment-amount {
        font-weight: 700;
        color: #1f2b46;
    }

    .payment-submeta {
        font-size: .75rem;
        color: #7b879d;
        margin-top: .15rem;
    }

    #tbl-pembayaran tbody tr {
        transition: transform .15s ease, box-shadow .15s ease;
    }

    #tbl-pembayaran tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 9999px rgba(76, 112, 213, .045);
    }

    @media (max-width: 767.98px) {
        .payment-stat-card {
            padding: .8rem .85rem;
        }

        .payment-amount {
            font-size: .9rem;
        }
    }
</style>

<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-credit-card"></i></div>
    <div>
        <h2>Pembayaran</h2>
        <p>Kelola pembayaran periodik penghuni dan proses approval pemilik dengan cepat.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="payment-stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="payment-stat-icon bg-label-primary text-primary"><i class="bx bx-receipt"></i></span>
                <small class="text-body-secondary">Total Transaksi</small>
            </div>
            <h5 class="mb-0">{{ number_format($totalTransaksi, 0, ',', '.') }}</h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="payment-stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="payment-stat-icon bg-label-success text-success"><i class="bx bx-badge-check"></i></span>
                <small class="text-body-secondary">Sudah Lunas</small>
            </div>
            <h5 class="mb-0">{{ number_format($totalLunas, 0, ',', '.') }}</h5>
            <small class="text-body-secondary d-block mt-1">Rp {{ number_format($nominalLunas, 0, ',', '.') }}</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="payment-stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="payment-stat-icon bg-label-warning text-warning"><i class="bx bx-time-five"></i></span>
                <small class="text-body-secondary">Belum Lunas</small>
            </div>
            <h5 class="mb-0">{{ number_format($totalPending, 0, ',', '.') }}</h5>
            <small class="text-body-secondary d-block mt-1">Rp {{ number_format($nominalPending, 0, ',', '.') }}</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="payment-stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="payment-stat-icon bg-label-info text-info"><i class="bx bx-wallet"></i></span>
                <small class="text-body-secondary">Total Nominal</small>
            </div>
            <h6 class="mb-0">Rp {{ number_format($totalNominal, 0, ',', '.') }}</h6>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Data Pembayaran</h5>
            <small class="text-body-secondary">Kelola pembayaran periodik penghuni.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pembayarans.export', request()->only(['q', 'bulan', 'status', 'metode'])) }}" class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export CSV</a>
            @canMenu('manajemen_sewa.data_sewa', 'create')
            <a href="{{ route('pembayarans.bulk-billing') }}" class="btn btn-outline-primary"><i class="bx bx-receipt me-1"></i>Bulk Billing</a>
            <a href="{{ route('pembayarans.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Pembayaran</a>
            @endCanMenu
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('pembayarans.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="payment-search" class="form-label">Penghuni atau kamar</label>
                <input id="payment-search" type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nama penghuni / nomor kamar" class="form-control">
            </div>
            <div class="col-md-2">
                <label for="payment-month" class="form-label">Bulan periode</label>
                <input id="payment-month" type="month" name="bulan" value="{{ request('bulan') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="payment-status" class="form-label">Status pembayaran</label>
                <select id="payment-status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="belum_lunas" @selected(request('status') === 'belum_lunas')>Belum lunas</option>
                    <option value="lunas" @selected(request('status') === 'lunas')>Lunas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="payment-method" class="form-label">Metode pembayaran</label>
                <select id="payment-method" name="metode" class="form-select">
                    <option value="">Semua metode</option>
                    <option value="cash" @selected(request('metode') === 'cash')>Tunai</option>
                    <option value="transfer" @selected(request('metode') === 'transfer')>Transfer</option>
                    <option value="e-wallet" @selected(request('metode') === 'e-wallet')>E-wallet</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <a href="{{ route('pembayarans.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
        <small class="text-body-secondary d-block mt-2">Ringkasan dan Export CSV mengikuti semua filter di atas. Pencarian pada tabel hanya mengubah baris yang ditampilkan.</small>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-pembayaran" data-mobile-cols="2,4,5,6">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-pembayaran"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Penghuni</th>
                    <th>Periode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                @foreach($pembayarans as $pembayaran)
                @php
                    $nama = $pembayaran->sewa->penghuni->nama ?? 'Unknown';
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
                                <small class="d-block text-body-secondary">Kamar {{ $pembayaran->sewa->kamar->nomor ?? '-' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ optional($pembayaran->periode)->format('m/Y') }}</td>
                    <td>
                        <div class="payment-amount">Rp {{ number_format((float)$pembayaran->jumlah,0,',','.') }}</div>
                        <div class="payment-submeta">{{ ucfirst(str_replace('-', ' ', $pembayaran->metode ?? '-')) }}</div>
                    </td>
                    <td>
                        @if($pembayaran->status === 'lunas')
                            <span class="badge rounded-pill bg-success">Lunas</span>
                        @elseif($pembayaran->status === 'belum_lunas')
                            <span class="badge rounded-pill bg-warning text-dark">Menunggu Approve Pemilik</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary">{{ ucfirst(str_replace('_',' ',$pembayaran->status)) }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @canMenu('manajemen_sewa.data_sewa', 'update')
                            @if($pembayaran->status === 'belum_lunas')
                                <form method="POST" action="{{ route('pembayarans.approve', $pembayaran) }}" class="d-inline" onsubmit="return confirm('Setujui pembayaran ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-action-payment"><i class="icon-base bx bx-check-shield me-1"></i>Approve</button>
                                </form>
                            @endif
                            @endCanMenu
                            <a href="{{ route('pembayarans.show', $pembayaran) }}" class="btn btn-sm btn-action-detail">
                                <i class="icon-base bx bx-detail me-1"></i>Detail
                            </a>
                            @canMenu('manajemen_sewa.data_sewa', 'update')
                            <a href="{{ route('pembayarans.edit', $pembayaran) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endCanMenu
                            @canMenu('manajemen_sewa.data_sewa', 'delete')
                            <form method="POST" action="{{ route('pembayarans.destroy', $pembayaran) }}" onsubmit="return confirm('Hapus pembayaran ini?')" class="d-inline">
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
