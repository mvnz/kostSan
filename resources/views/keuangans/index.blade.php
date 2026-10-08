@php
$pageTitle = 'Keuangan';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-wallet"></i></div>
    <div>
        <h2>Keuangan</h2>
        <p>Pencatatan kas masuk dan pengeluaran operasional kost.</p>
    </div>
</div>

@canMenu('manajemen_sewa.data_sewa', 'view')
<a href="{{ route('keuangans.reconciliation') }}" class="btn btn-outline-primary mb-3">Rekonsiliasi Pembayaran–Keuangan</a>
@endCanMenu
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Total Pemasukan</small>
                <h5 class="mb-0 text-success">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Total Pengeluaran</small>
                <h5 class="mb-0 text-danger">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Saldo</small>
                <h5 class="mb-0 {{ $saldo >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($saldo, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-body-secondary d-block mb-1">Bulan Ini (Masuk - Keluar)</small>
                @php
                    $saldoBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;
                @endphp
                <h6 class="mb-0 {{ $saldoBulanIni >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($saldoBulanIni, 0, ',', '.') }}</h6>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Pencatatan Keuangan</h5>
            <small class="text-body-secondary">Kas masuk dan keluar operasional kost.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <select id="filter-jenis-keuangan" class="form-select" style="min-width: 165px;">
                <option value="">Semua Jenis</option>
                <option value="pemasukan">Pemasukan</option>
                <option value="pengeluaran">Pengeluaran</option>
            </select>
            <input type="month" id="filter-bulan-keuangan" class="form-control" style="min-width: 175px;" value="{{ now()->format('Y-m') }}" data-current-month="{{ now()->format('Y-m') }}">
            <button type="button" class="btn btn-outline-secondary" id="reset-filter-keuangan"><i class="bx bx-reset me-1"></i>Reset</button>
            <button class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export</button>
            @canMenu('keuangan.data_keuangan', 'create')
            <a href="{{ route('keuangans.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Transaksi</a>
            @endCanMenu
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-keuangan" data-mobile-cols="2,4,7">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-keuangan"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Transaksi</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Deskripsi</th>
                    <th>Bukti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($keuangans as $item)
                <tr>
                    <td><div class="form-check"><input type="checkbox" class="form-check-input"></div></td>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded-circle {{ $item->jenis === 'pemasukan' ? 'bg-label-success' : 'bg-label-danger' }}">
                                    <i class="icon-base bx {{ $item->jenis === 'pemasukan' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                                </span>
                            </div>
                            <div>
                                <span class="d-none">BULAN:{{ optional($item->tanggal)->format('Y-m') }}</span>
                                <strong>{{ $item->kategori }}</strong>
                                <small class="d-block text-body-secondary">{{ optional($item->tanggal)->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="d-none">JENIS:{{ $item->jenis }}</span>
                        @if($item->jenis === 'pemasukan')
                            <span class="badge rounded-pill bg-label-success">Pemasukan</span>
                        @else
                            <span class="badge rounded-pill bg-label-danger">Pengeluaran</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format((float)$item->jumlah,0,',','.') }}</td>
                    <td>
                        {{ $item->deskripsi ?: '-' }}
                        @if($item->payment_id)
                            <span class="badge bg-label-info d-block mt-1" style="width: fit-content;">Otomatis · Pembayaran #{{ $item->payment_id }}</span>
                        @endif
                    </td>
                    <td>
                        @if($item->bukti_path)
                            <a href="{{ route('secure-files.show', ['path' => $item->bukti_path]) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bx bx-file me-1"></i>Lihat
                            </a>
                        @else
                            <span class="text-body-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @if($item->payment_id)
                                @canMenu('manajemen_sewa.data_sewa', 'view')
                                <a href="{{ route('pembayarans.show', $item->payment_id) }}" class="btn btn-sm btn-action-detail">
                                    <i class="icon-base bx bx-receipt me-1"></i>Pembayaran
                                </a>
                                @else
                                <span class="text-body-secondary small">Sumber otomatis</span>
                                @endCanMenu
                            @else
                                <a href="{{ route('keuangans.show', $item) }}" class="btn btn-sm btn-action-detail">
                                    <i class="icon-base bx bx-detail me-1"></i>Detail
                                </a>
                            @canMenu('keuangan.data_keuangan', 'update')
                            <a href="{{ route('keuangans.edit', $item) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endCanMenu
                            @canMenu('keuangan.data_keuangan', 'delete')
                            <form method="POST" action="{{ route('keuangans.destroy', $item) }}" onsubmit="return confirm('Hapus transaksi ini?')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                            </form>
                            @endCanMenu
                            @endif
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
        const table = $('#tbl-keuangan').DataTable();
        const colTransaksi = 2;
        const colJenis = 3;

        function applyKeuanganFilters() {
            const jenis = $('#filter-jenis-keuangan').val();
            const bulan = $('#filter-bulan-keuangan').val();

            table.column(colJenis).search(jenis ? ('JENIS:' + jenis) : '', false, false);
            table.column(colTransaksi).search(bulan ? ('BULAN:' + bulan) : '', false, false);
            table.draw();
        }

        $('#filter-jenis-keuangan, #filter-bulan-keuangan').on('change', applyKeuanganFilters);
        $('#reset-filter-keuangan').on('click', function () {
            const currentMonth = $('#filter-bulan-keuangan').data('current-month');
            $('#filter-jenis-keuangan').val('');
            $('#filter-bulan-keuangan').val(currentMonth || '');
            applyKeuanganFilters();
        });

        applyKeuanganFilters();
    });
</script>
@endsection

