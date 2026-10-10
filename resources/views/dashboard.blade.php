@extends('layouts.app')

@section('content')

<style>
    .dashboard-modern .card {
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .dashboard-modern .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(16, 34, 75, .09);
    }

    .dashboard-welcome-card {
        background: linear-gradient(135deg, #ffffff 0%, #f4f8ff 100%);
        border: 1px solid #dbe7ff;
        overflow: hidden;
        position: relative;
    }

    .dashboard-welcome-card::after {
        content: '';
        position: absolute;
        right: -48px;
        top: -48px;
        width: 170px;
        height: 170px;
        border-radius: 999px;
        background: rgba(59, 130, 246, .12);
        pointer-events: none;
    }

    .dashboard-kpi-card .card-title,
    .dashboard-kpi-card h4 {
        letter-spacing: -.2px;
    }

    .dashboard-chip {
        border: 1px solid #dbe6ff;
        background: #f5f8ff;
        color: #284f93;
        border-radius: 999px;
        padding: .28rem .62rem;
        font-size: .74rem;
        font-weight: 600;
    }

    .dashboard-focus-card .progress {
        border-radius: 999px;
        background: #edf2fb;
    }

    .dashboard-side-equal {
        height: 100%;
    }

    .dashboard-side-equal .row {
        height: 100%;
    }

    .dashboard-side-equal .col-6 {
        display: flex;
    }

    .dashboard-side-equal .card {
        width: 100%;
        height: 100%;
    }

    @media (max-width: 767.98px) {
        .dashboard-welcome-card .card-body {
            padding-bottom: .75rem !important;
        }

        .dashboard-chip {
            font-size: .7rem;
            padding: .24rem .52rem;
        }

        .dashboard-side-equal,
        .dashboard-side-equal .row {
            height: auto;
        }

        .dashboard-side-equal .col-6 {
            display: block;
        }
    }
</style>

<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-home-alt-2"></i></div>
    <div>
        <h2>Dashboard Operasional</h2>
        <p>Pantau performa kost harian dari hunian, transaksi, hingga tindak lanjut pembayaran dalam satu layar.</p>
    </div>
</div>

<div class="dashboard-modern">

@if(($cleanupQueue['count'] ?? 0) > 0)
<div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6" role="alert">
    <div class="d-flex align-items-start gap-3">
        <i class="icon-base bx bx-error-circle fs-3 flex-shrink-0" aria-hidden="true"></i>
        <div>
            <strong>Antrean file privat perlu ditangani</strong>
            <div class="small mt-1">
                {{ $cleanupQueue['count'] }} file menunggu pembersihan;
                Percobaan tertinggi: {{ $cleanupQueue['max_attempts'] }}.
                @if($cleanupQueue['oldest_at'])
                    Antrean tertua {{ $cleanupQueue['oldest_at']->diffForHumans() }}.
                @endif
            </div>
        </div>
    </div>
    <div class="small text-md-end">
        Minta admin server memeriksa<br>
        <code class="text-break">php artisan private-files:cleanup --dry-run</code>
    </div>
</div>
@endif

{{-- ══ ROW 1: Welcome Card + 2 KPI Cards ══ --}}
<div class="row">

    {{-- Welcome Card --}}
    <div class="col-xxl-8 mb-6 order-0">
        <div class="card dashboard-welcome-card">
            <div class="d-flex align-items-start row">
                <div class="col-sm-7">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-3">Selamat Datang, Admin</h5>
                        <p class="mb-6">
                            Kelola operasional kost dengan mudah dan efisien.<br />
                            Semua data tersedia dalam satu panel terpadu.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="dashboard-chip">{{ $stat['kamar_terisi'] }}/{{ $stat['total_kamar'] }} kamar terisi</span>
                            <span class="dashboard-chip">{{ $stat['penghuni_aktif'] }} penghuni aktif</span>
                            <span class="dashboard-chip">Rp {{ number_format($stat['tagihan_belum_lunas'], 0, ',', '.') }} pending</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('sewas.index') }}" class="btn btn-sm btn-outline-primary">Kelola Sewa</a>
                            <a href="{{ route('keuangans.create') }}" class="btn btn-sm btn-primary">+ Tambah Transaksi</a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-5 text-center text-sm-left">
                    <div class="card-body pb-0 px-0 px-md-6">
                        <div style="font-size:7rem;line-height:1;padding-top:14px;padding-bottom:6px;filter:drop-shadow(0 8px 20px rgba(102,110,255,0.35));">🏠</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2 KPI Cards --}}
    <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">

            {{-- Total Pemasukan --}}
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-money icon-md"></i>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('laporan-keuangan.index') }}">Lihat Laporan</a>
                                    <a class="dropdown-item" href="{{ route('keuangans.index') }}">Keuangan</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Total Pemasukan</p>
                        <h4 class="card-title mb-3">
                            Rp {{ $stat['total_pemasukan'] >= 1000000
                                ? number_format($stat['total_pemasukan']/1000000, 1) . 'Jt'
                                : number_format($stat['total_pemasukan']/1000, 0) . 'k' }}
                        </h4>
                        <small class="text-success fw-medium">
                            <i class="icon-base bx bx-up-arrow-alt"></i>
                            {{ $stat['kamar_terisi'] }}/{{ $stat['total_kamar'] }} kamar
                        </small>
                    </div>
                </div>
            </div>

            {{-- Penghuni Aktif --}}
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="icon-base bx bx-user icon-md"></i>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('penghunis.index') }}">Lihat Penghuni</a>
                                    <a class="dropdown-item" href="{{ route('sewas.index') }}">Data Sewa</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Penghuni Aktif</p>
                        <h4 class="card-title mb-3">{{ $stat['penghuni_aktif'] }}</h4>
                        <small class="text-warning fw-medium">
                            <i class="icon-base bx bx-user-plus"></i>
                            {{ $stat['total_penghuni'] }} terdaftar
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
{{-- / Row 1 --}}

{{-- ══ ROW 2: Ringkasan Keuangan + Occupancy Stats ══ --}}
<div class="row">

    {{-- Ringkasan Keuangan --}}
    <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6">
        <div class="card dashboard-focus-card">
            <div class="row row-bordered g-0">

                <div class="col-lg-8">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Ringkasan Keuangan</h5>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded icon-lg text-body-secondary"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('laporan-keuangan.index') }}">Laporan Lengkap</a>
                                <a class="dropdown-item" href="{{ route('keuangans.create') }}">Tambah Transaksi</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pb-0 px-5 py-4">
                        @php
                            $pengeluaranPct = $stat['total_pemasukan'] > 0
                                ? min(round(($stat['total_pengeluaran'] / $stat['total_pemasukan']) * 100), 100)
                                : 0;
                            $occupancyPct = $stat['total_kamar'] > 0
                                ? round(($stat['kamar_terisi'] / $stat['total_kamar']) * 100)
                                : 0;
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-medium small">Pemasukan</span>
                                <span class="text-success small">Rp {{ number_format($stat['total_pemasukan'], 0, ',', '.') }}</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-success" style="width:100%"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-medium small">Pengeluaran</span>
                                <span class="text-danger small">Rp {{ number_format($stat['total_pengeluaran'], 0, ',', '.') }}</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-danger" style="width:{{ $pengeluaranPct }}%"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-medium small">Tingkat Hunian</span>
                                <span class="text-primary small">{{ $stat['kamar_terisi'] }} / {{ $stat['total_kamar'] }} kamar</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-primary" style="width:{{ $occupancyPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Saldo Summary --}}
                <div class="col-lg-4">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-6 px-4 text-center" style="min-height:240px;">
                        <div class="mb-4">
                            <h5 class="text-nowrap mb-1">Saldo Keuangan</h5>
                            <span class="badge {{ $stat['saldo_keuangan'] >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ $stat['saldo_keuangan'] >= 0 ? 'Positif' : 'Defisit' }}
                            </span>
                        </div>
                        <h3 class="fw-bold mb-2 {{ $stat['saldo_keuangan'] >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format(abs($stat['saldo_keuangan']) / 1000000, 1) }}Jt
                        </h3>
                        <div class="d-flex gap-6 mt-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar">
                                    <span class="avatar-initial rounded-2 bg-label-success">
                                        <i class="icon-base bx bx-up-arrow-alt text-success"></i>
                                    </span>
                                </div>
                                <div class="text-start">
                                    <small class="text-body-secondary d-block">Masuk</small>
                                    <h6 class="mb-0">Rp {{ number_format($stat['total_pemasukan'] / 1000000, 1) }}Jt</h6>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar">
                                    <span class="avatar-initial rounded-2 bg-label-danger">
                                        <i class="icon-base bx bx-down-arrow-alt text-danger"></i>
                                    </span>
                                </div>
                                <div class="text-start">
                                    <small class="text-body-secondary d-block">Keluar</small>
                                    <h6 class="mb-0">Rp {{ number_format($stat['total_pengeluaran'] / 1000000, 1) }}Jt</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Occupancy + Mini Stats --}}
    <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 mb-6 dashboard-side-equal">
        @php
            $pemasukanBulanIni = (float) (collect($chartData['pemasukan'] ?? [])->last() ?? 0);
            $pengeluaranBulanIni = (float) (collect($chartData['pengeluaran'] ?? [])->last() ?? 0);
            $labelBulanIni = now()->translatedFormat('F Y');
        @endphp
        <div class="row g-3">

            {{-- Pemasukan Bulan Ini --}}
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-trending-up icon-md"></i>
                                </span>
                            </div>
                        </div>
                        <p class="mb-1">Pemasukan Bulan Ini</p>
                        <h4 class="card-title mb-3">Rp {{ number_format($pemasukanBulanIni, 0, ',', '.') }}</h4>
                        <small class="text-success fw-medium">
                            <i class="icon-base bx bx-calendar"></i>
                            {{ $labelBulanIni }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Pengeluaran Bulan Ini --}}
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="icon-base bx bx-trending-down icon-md"></i>
                                </span>
                            </div>
                        </div>
                        <p class="mb-1">Pengeluaran Bulan Ini</p>
                        <h4 class="card-title mb-3">Rp {{ number_format($pengeluaranBulanIni, 0, ',', '.') }}</h4>
                        <small class="text-danger fw-medium">
                            <i class="icon-base bx bx-calendar"></i>
                            {{ $labelBulanIni }}
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
{{-- / Row 2 --}}

{{-- ══ ROW 3: Hunian + Kamar Stats + Pembayaran + Sewa Terbaru ══ --}}
<div class="row">

    {{-- Hunian Donut --}}
    <div class="col-md-6 col-lg-6 col-xl-3 order-0 mb-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-4 flex-wrap">
                    <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                        <div class="card-title mb-4">
                            <h5 class="text-nowrap mb-1">Hunian Kost</h5>
                            <span class="badge bg-label-primary">{{ date('Y') }}</span>
                        </div>
                        <div class="mt-sm-auto">
                            <span class="text-{{ $occupancyPct >= 50 ? 'success' : 'warning' }} text-nowrap fw-medium">
                                <i class="icon-base bx bx-up-arrow-alt"></i>
                                {{ $occupancyPct }}%
                            </span>
                            <h4 class="mb-0">{{ $stat['kamar_terisi'] }} Terisi</h4>
                        </div>
                    </div>
                    @php
                        $r = 30; $cx = 40; $cy = 40;
                        $circ = 2 * M_PI * $r;
                        $filled = ($occupancyPct / 100) * $circ;
                        $empty = $circ - $filled;
                        $offset = $circ / 4;
                    @endphp
                    <svg viewBox="0 0 80 80" width="90" height="90">
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#f0f2f8" stroke-width="10"/>
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#696cff" stroke-width="10"
                            stroke-dasharray="{{ round($filled, 2) }} {{ round($empty, 2) }}"
                            stroke-dashoffset="{{ round($offset, 2) }}"
                            stroke-linecap="round"/>
                        <text x="{{ $cx }}" y="{{ $cy + 5 }}" text-anchor="middle" font-size="13" font-weight="700" fill="#566a7f">{{ $occupancyPct }}%</text>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Kamar Statistics --}}
    <div class="col-md-6 col-lg-6 col-xl-3 order-0 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">Status Kamar</h5>
                    <p class="card-subtitle">Ringkasan ketersediaan kamar saat ini</p>
                </div>
                <div class="dropdown">
                    <button class="btn text-body-secondary p-0" type="button" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('kamars.index') }}">Kelola Kamar</a>
                        <a class="dropdown-item" href="{{ route('kamars.create') }}">Tambah Kamar</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <ul class="p-0 m-0">
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="icon-base bx bx-door-open"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Tersedia</h6><small>Siap dihuni</small></div>
                            <div class="user-progress"><h6 class="mb-0">{{ $stat['kamar_tersedia'] }}</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="icon-base bx bx-user"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Terisi</h6><small>Sedang dihuni</small></div>
                            <div class="user-progress"><h6 class="mb-0">{{ $stat['kamar_terisi'] }}</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="icon-base bx bx-calendar"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Reservasi</h6><small>Calon penghuni</small></div>
                            <div class="user-progress"><h6 class="mb-0">{{ $stat['total_reservasi'] }}</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="icon-base bx bx-group"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Total Penghuni</h6><small>Terdaftar</small></div>
                            <div class="user-progress"><h6 class="mb-0">{{ $stat['total_penghuni'] }}</h6></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Pembayaran Terbaru --}}
    <div class="col-md-6 col-lg-6 col-xl-3 order-1 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Pembayaran Terbaru</h5>
                <div class="dropdown">
                    <button class="btn text-body-secondary p-0" type="button" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('pembayarans.index') }}">Lihat Semua</a>
                        <a class="dropdown-item" href="{{ route('pembayarans.create') }}">Tambah</a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-4">
                @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                <ul class="p-0 m-0">
                    @forelse($pembayaranTerbaru as $pb)
                        @php
                            $nama = $pb->sewa->penghuni->nama ?? 'Unknown';
                            $inisial = strtoupper(mb_substr(preg_replace('/\s+/', '', $nama), 0, 2));
                            $c = $avatarColors[$loop->index % 5];
                        @endphp
                        <li class="d-flex align-items-center {{ $loop->last ? '' : 'mb-6' }}">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded {{ $c }}">{{ $inisial }}</span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <small class="d-block text-body-secondary">Kamar {{ $pb->sewa->kamar->nomor ?? '-' }}</small>
                                    <h6 class="fw-normal mb-0">{{ $nama }}</h6>
                                </div>
                                <div class="user-progress d-flex align-items-center gap-2">
                                    <h6 class="fw-normal mb-0 {{ $pb->status === 'lunas' ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float)$pb->jumlah / 1000, 0, ',', '.') }}k
                                    </h6>
                                    <span class="badge rounded-pill {{ $pb->status === 'lunas' ? 'bg-label-success' : 'bg-label-danger' }}">
                                        {{ $pb->status === 'lunas' ? 'Lunas' : 'Tunda' }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center py-5 text-body-secondary">
                            <i class="icon-base bx bx-receipt icon-lg d-block mb-2"></i>
                            Belum ada pembayaran.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Sewa Terbaru --}}
    <div class="col-md-6 col-lg-6 col-xl-3 order-2 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Sewa Terbaru</h5>
                <a href="{{ route('sewas.index') }}" class="btn btn-sm btn-outline-primary">Semua</a>
            </div>
            <div class="card-body pt-4">
                <ul class="p-0 m-0">
                    @forelse($sewaTerbaru as $sewa)
                        @php
                            $nama2 = $sewa->penghuni->nama ?? 'Unknown';
                            $inisial2 = strtoupper(mb_substr(preg_replace('/\s+/', '', $nama2), 0, 2));
                            $c2 = $avatarColors[$loop->index % 5];
                        @endphp
                        <li class="d-flex align-items-center {{ $loop->last ? '' : 'mb-6' }}">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded {{ $c2 }}">{{ $inisial2 }}</span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <small class="d-block text-body-secondary">Kamar {{ $sewa->kamar->nomor ?? '-' }}</small>
                                    <h6 class="fw-normal mb-0">{{ $nama2 }}</h6>
                                </div>
                                <div class="user-progress">
                                    @if($sewa->status === 'aktif')
                                        <span class="badge rounded-pill bg-label-success">Aktif</span>
                                    @elseif($sewa->status === 'menunggak')
                                        <span class="badge rounded-pill bg-label-warning">Menunggak</span>
                                    @else
                                        <span class="badge rounded-pill bg-label-secondary">{{ ucfirst($sewa->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center py-5 text-body-secondary">
                            <i class="icon-base bx bx-calendar icon-lg d-block mb-2"></i>
                            Belum ada data sewa.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

</div>
{{-- / Row 3 --}}

<style>
    .sewa-expiring-header {
        gap: .75rem;
        border-bottom: 1px solid #eef2f8;
    }

    .sewa-expiring-btn {
        white-space: nowrap;
        border-radius: .55rem;
        padding: .45rem .8rem;
        font-weight: 600;
    }

    .sewa-expiring-list {
        list-style: none;
    }

    .sewa-expiring-item {
        padding: .25rem 0;
    }

    @media (max-width: 767.98px) {
        .sewa-expiring-header {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .sewa-expiring-btn {
            white-space: nowrap !important;
            padding: .42rem .7rem;
            font-size: .75rem;
            align-self: flex-start;
        }

        .sewa-expiring-item > .d-flex {
            align-items: flex-start !important;
        }

        .sewa-expiring-item .d-flex.w-100 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: .35rem !important;
        }
    }
</style>

{{-- ══ ROW 4: Sewa Akan Berakhir (7 Hari) ══ --}}
<div class="row">
    <div class="col-12 mb-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between sewa-expiring-header">
                <div>
                    <h5 class="card-title m-0">Masa Sewa Akan Habis (7 Hari)</h5>
                    <small class="text-body-secondary">Daftar penyewa aktif atau menunggak yang perlu di-follow up</small>
                </div>
                <a href="{{ route('sewas.index') }}" class="btn btn-sm btn-outline-warning sewa-expiring-btn">Lihat Data Sewa</a>
            </div>
            <div class="card-body pt-3">
                <ul class="p-0 m-0 sewa-expiring-list">
                    @forelse($sewaAkanBerakhir as $sewaHabis)
                        @php
                            $namaHabis = $sewaHabis->penghuni->nama ?? 'Unknown';
                            $inisialHabis = strtoupper(mb_substr(preg_replace('/\s+/', '', $namaHabis), 0, 2));
                            $sisaHari = max(0, now()->startOfDay()->diffInDays($sewaHabis->tanggal_keluar, false));
                        @endphp
                        <li class="d-flex align-items-center sewa-expiring-item {{ $loop->last ? '' : 'mb-4' }}">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-warning">{{ $inisialHabis }}</span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-1">{{ $namaHabis }}</h6>
                                    <small class="d-block text-body-secondary">
                                        Kamar {{ $sewaHabis->kamar->nomor ?? '-' }}
                                        • Berakhir {{ optional($sewaHabis->tanggal_keluar)->format('d M Y') }}
                                    </small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($sewaHabis->status === 'menunggak')
                                    <span class="badge rounded-pill bg-label-warning">Menunggak</span>
                                    @endif
                                    <span class="badge rounded-pill {{ $sisaHari <= 3 ? 'bg-label-danger' : 'bg-label-warning' }}">
                                        {{ $sisaHari }} hari lagi
                                    </span>
                                    <a href="{{ route('sewas.show', $sewaHabis) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center py-5 text-body-secondary">
                            <i class="icon-base bx bx-check-shield icon-lg d-block mb-2"></i>
                            Tidak ada masa sewa yang habis dalam 7 hari ke depan.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
{{-- / Row 4 --}}

{{-- ══ ROW 5: Charts — Tren Keuangan + Tren Hunian ══ --}}
<div class="row">

    {{-- Tren Pendapatan vs Pengeluaran --}}
    <div class="col-12 col-xl-7 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0">Tren Keuangan <small class="text-body-secondary fw-normal fs-6">6 bulan terakhir</small></h5>
                <a href="{{ route('laporan-keuangan.index') }}" class="btn btn-sm btn-outline-primary">Laporan</a>
            </div>
            <div class="card-body pt-3">
                <canvas id="chartKeuangan" height="220"></canvas>
            </div>
        </div>
    </div>

    {{-- Grafik Hunian per Bulan --}}
    <div class="col-12 col-xl-5 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0">Hunian per Bulan <small class="text-body-secondary fw-normal fs-6">6 bulan</small></h5>
                <a href="{{ route('kamars.sewa') }}" class="btn btn-sm btn-outline-primary">Peta Kamar</a>
            </div>
            <div class="card-body pt-3">
                <p class="small text-body-secondary">Kamar unik dengan sewa aktif atau selesai pada sebagian bulan; checkout eksklusif. Bukan rata-rata harian. Pembagi memakai jumlah kamar saat ini.</p>
                <canvas id="chartHunian" height="220"></canvas>
            </div>
        </div>
    </div>

</div>
{{-- / Row 5 --}}

{{-- ══ ROW 6: Kamar Paling Lama Kosong ══ --}}
@if($kamarKosong->count())
<div class="row">
    <div class="col-12 mb-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title m-0">Kamar Paling Lama Kosong</h5>
                    <small class="text-body-secondary">Kamar tersedia yang butuh perhatian segera</small>
                </div>
                <a href="{{ route('kamars.sewa') }}" class="btn btn-sm btn-outline-warning">Sewa Kamar</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No. Kamar</th>
                            <th>Tipe</th>
                            <th>Harga/Bulan</th>
                            <th>Kosong Sejak</th>
                            <th>Lama Kosong</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kamarKosong as $item)
                        <tr>
                            <td><span class="badge bg-label-primary fw-bold px-3 py-2">{{ $item['kamar']->nomor }}</span></td>
                            <td>{{ $item['kamar']->tipe ?: '-' }}</td>
                            <td>Rp {{ number_format((float)$item['kamar']->harga_bulanan, 0, ',', '.') }}</td>
                            <td>{{ $item['label'] }}</td>
                            <td>
                                @if($item['lama_hari'] === 9999)
                                    <span class="badge bg-label-secondary">Belum pernah terisi</span>
                                @elseif($item['lama_hari'] >= 30)
                                    <span class="badge bg-label-danger">{{ $item['lama_hari'] }} hari</span>
                                @elseif($item['lama_hari'] >= 14)
                                    <span class="badge bg-label-warning">{{ $item['lama_hari'] }} hari</span>
                                @else
                                    <span class="badge bg-label-info">{{ $item['lama_hari'] }} hari</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('kamars.show', $item['kamar']) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
{{-- / Row 6 --}}

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f0f2f8' }, ticks: { font: { size: 11 } } },
        },
    };

    // Chart Keuangan
    new Chart(document.getElementById('chartKeuangan'), {
        type: 'bar',
        data: {
            labels: @json($chartData['labels']),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: @json($chartData['pemasukan']),
                    backgroundColor: 'rgba(105, 108, 255, 0.82)',
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
                {
                    label: 'Pengeluaran',
                    data: @json($chartData['pengeluaran']),
                    backgroundColor: 'rgba(255, 159, 64, 0.78)',
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
            ],
        },
        options: {
            ...chartDefaults,
            scales: {
                ...chartDefaults.scales,
                y: {
                    ...chartDefaults.scales.y,
                    ticks: {
                        callback: v => v >= 1e6 ? (v/1e6).toFixed(1)+'Jt' : (v/1e3).toFixed(0)+'k',
                        font: { size: 11 },
                    },
                },
            },
        },
    });

    // Chart Hunian
    new Chart(document.getElementById('chartHunian'), {
        type: 'line',
        data: {
            labels: @json($hunianChart['labels']),
            datasets: [
                {
                    label: 'Tingkat Hunian (%)',
                    data: @json($hunianChart['pct']),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2.5,
                },
            ],
        },
        options: {
            ...chartDefaults,
            scales: {
                ...chartDefaults.scales,
                y: {
                    ...chartDefaults.scales.y,
                    min: 0, max: 100,
                    ticks: { callback: v => v + '%', font: { size: 11 } },
                },
            },
        },
    });
})();
</script>
@endsection
