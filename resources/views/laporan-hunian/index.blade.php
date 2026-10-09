@php
$pageTitle = 'Laporan Hunian';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-building-house"></i></div>
    <div>
        <h2>Laporan Hunian</h2>
        <p>Statistik tingkat hunian kamar per bulan dalam satu tahun.</p>
    </div>
</div>

<div class="alert alert-info">Setiap kamar dihitung sekali bila memiliki sewa {{ $cakupan === 'riwayat' ? 'aktif atau selesai' : 'aktif' }} pada sebagian bulan. Tanggal checkout tidak dihitung. Sewa menunggak tidak dimasukkan. Histori membutuhkan tanggal checkout yang valid. Angka ini bukan rata-rata hunian harian; pembagi memakai jumlah kamar yang ada saat ini.</div>
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="tahun-hunian" class="form-label fw-semibold">Tahun</label>
                <select id="tahun-hunian" name="tahun" class="form-select">
                    @foreach($tahunList as $t)
                    <option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="cakupan-hunian" class="form-label fw-semibold">Cakupan sewa</label>
                <select id="cakupan-hunian" name="cakupan" class="form-select">
                    <option value="aktif" @selected($cakupan === 'aktif')>Sewa aktif</option>
                    <option value="riwayat" @selected($cakupan === 'riwayat')>Aktif dan riwayat selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Total Kamar</small>
                <h5 class="mb-0 text-primary">{{ $totalKamar }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Rata-rata Hunian {{ $tahun }}</small>
                <h5 class="mb-0 {{ $avgHunian >= 70 ? 'text-success' : ($avgHunian >= 40 ? 'text-warning' : 'text-danger') }}">{{ $avgHunian }}%</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Terisi Saat Ini</small>
                <h5 class="mb-0 text-success">{{ $kamars->filter(fn($k) => $k->sewas->isNotEmpty())->count() }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header p-3"><h5 class="mb-0">Tren Hunian {{ $tahun }}</h5></div>
    <div class="card-body p-3">
        <canvas id="chartHunianTahunan" height="80"></canvas>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header p-3"><h5 class="mb-0">Rekap Per Bulan</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Bulan</th>
                        <th class="text-center">Terisi</th>
                        <th class="text-center">Kosong</th>
                        <th class="text-center">Total</th>
                        <th>Hunian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bulanData as $b)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $b['bulan'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-label-success">{{ $b['terisi'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-label-danger">{{ $b['kosong'] }}</span>
                        </td>
                        <td class="text-center text-muted">{{ $totalKamar }}</td>
                        <td style="min-width:140px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-fill" style="height:8px;">
                                    <div class="progress-bar {{ $b['pct'] >= 70 ? 'bg-success' : ($b['pct'] >= 40 ? 'bg-warning' : 'bg-danger') }}"
                                        style="width:{{ $b['pct'] }}%"></div>
                                </div>
                                <span style="font-size:12px;min-width:36px;">{{ $b['pct'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header p-3"><h5 class="mb-0">Status Kamar Saat Ini</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-sm align-middle mb-0" id="tbl-hunian-kamar" data-mobile-cols="0,1,2">
                <thead>
                    <tr>
                        <th>Kamar</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Penghuni</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kamars as $k)
                    @php
                        $sewaAktif = $k->sewas->first();
                        $reservasiTerkonfirmasi = $k->confirmedReservations->first();
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $k->nomor }}</td>
                        <td>{{ $k->tipe }}</td>
                        <td>
                            @if($sewaAktif)
                                <span class="badge bg-label-success">Terisi</span>
                            @elseif($k->status === 'perbaikan')
                                <span class="badge bg-label-warning">Perbaikan</span>
                            @else
                                <span class="badge bg-label-danger">Kosong</span>
                            @endif
                            @if($reservasiTerkonfirmasi)
                                <span class="badge bg-label-info ms-1"><i class="bx bx-calendar-check me-1"></i>Reservasi</span>
                            @endif
                        </td>
                        <td>{{ $sewaAktif?->penghuni?->nama ?? $reservasiTerkonfirmasi?->penghuni?->nama ?? '-' }}</td>
                        <td>{{ optional($sewaAktif?->tanggal_masuk ?? $reservasiTerkonfirmasi?->rencana_masuk)->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ optional($sewaAktif?->tanggal_keluar ?? $reservasiTerkonfirmasi?->rencana_keluar)->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartHunianTahunan'), {
    type: 'bar',
    data: {
        labels: @json(collect($bulanData)->pluck('bulan_short')),
        datasets: [{
            label: 'Kamar Terisi',
            data: @json(collect($bulanData)->pluck('terisi')),
            backgroundColor: '#696cff33',
            borderColor: '#696cff',
            borderWidth: 2,
            borderRadius: 6,
            yAxisID: 'y',
        }, {
            label: 'Hunian (%)',
            data: @json(collect($bulanData)->pluck('pct')),
            type: 'line',
            borderColor: '#20c997',
            backgroundColor: 'transparent',
            borderWidth: 2,
            pointRadius: 4,
            tension: 0.4,
            yAxisID: 'y2',
        }],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index' },
        scales: {
            y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Jumlah Kamar' } },
            y2: { beginAtZero: true, position: 'right', max: 100, title: { display: true, text: 'Hunian (%)' }, grid: { drawOnChartArea: false } },
        },
    },
});
</script>
@endsection
