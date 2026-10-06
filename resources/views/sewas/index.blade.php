@php
$pageTitle = 'Sewa & Pembayaran';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-home-circle"></i></div>
    <div>
        <h2>Sewa & Pembayaran</h2>
        <p>Kelola data sewa aktif dan histori pembayaran bulanan penghuni.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <ul class="nav nav-tabs card-header-tabs" id="sewaTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-sewa" data-bs-toggle="tab" data-bs-target="#panel-sewa" type="button" role="tab">
                    <i class="bx bx-home-circle me-1"></i>Sewa
                    <span class="badge bg-label-primary ms-1">{{ $sewas->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-pembayaran" data-bs-toggle="tab" data-bs-target="#panel-pembayaran" type="button" role="tab">
                    <i class="bx bx-receipt me-1"></i>Pembayaran
                    <span class="badge bg-label-success ms-1">{{ $pembayarans->count() }}</span>
                </button>
            </li>
        </ul>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export</button>
            @canMenu('manajemen_sewa.data_sewa', 'create')
            <a href="{{ route('sewas.create') }}" class="btn btn-primary btn-add-sewa"><i class="bx bx-plus me-1"></i>Tambah Sewa</a>
            <a href="{{ route('pembayarans.create') }}" class="btn btn-primary btn-add-pembayaran d-none"><i class="bx bx-plus me-1"></i>Tambah Pembayaran</a>
            @endCanMenu
        </div>
    </div>

    @if(session('sewa_payment_link'))
        <div class="px-4 pb-2">
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
                <div>
                    <strong>Link pembayaran sewa baru:</strong>
                    <div><a href="{{ session('sewa_payment_link') }}" target="_blank">{{ session('sewa_payment_link') }}</a></div>
                    <small class="text-body-secondary">Link ini hanya berlaku sekali dan otomatis expire setelah submit.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-copy-link="{{ session('sewa_payment_link') }}">
                    <i class="bx bx-copy me-1"></i>Copy
                </button>
            </div>
        </div>
    @endif

    <div class="tab-content">
        {{-- ═══ TAB SEWA ═══ --}}
        <div class="tab-pane fade show active" id="panel-sewa" role="tabpanel">
            <div class="card-datatable table-responsive">
                <table class="table datatable" id="tbl-sewa" data-mobile-cols="2,5,6">
                    <thead>
                        <tr>
                            <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-sewa"></div></th>
                            <th data-orderable="false">#</th>
                            <th>Penghuni</th>
                            <th>Tanggal Masuk</th>
                            <th>Biaya/Bulan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                        @foreach($sewas as $sewa)
                        @php
                            $nama = $sewa->penghuni->nama ?? 'Unknown';
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
                                        <small class="d-block text-body-secondary">Kamar {{ $sewa->kamar->nomor ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ optional($sewa->tanggal_masuk)->format('d/m/Y') }}</td>
                            <td>Rp {{ number_format((float)$sewa->biaya_bulanan,0,',','.') }}</td>
                            <td>
                                @if($sewa->status === 'aktif')
                                    <span class="badge rounded-pill bg-label-success">Aktif</span>
                                @elseif($sewa->status === 'menunggak')
                                    <span class="badge rounded-pill bg-label-warning">Menunggak</span>
                                @else
                                    <span class="badge rounded-pill bg-label-secondary">{{ ucfirst($sewa->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 action-stack-mobile">
                                    <a href="{{ route('sewas.show', $sewa) }}" class="btn btn-sm btn-action-detail">
                                        <i class="icon-base bx bx-detail me-1"></i>Detail
                                    </a>
                                    @canMenu('manajemen_sewa.data_sewa', 'create')
                                    <a href="{{ route('pembayarans.create') }}?sewa_id={{ $sewa->id }}" class="btn btn-sm btn-action-payment">
                                        <i class="icon-base bx bx-money me-1"></i>Bayar
                                    </a>
                                    <form method="POST" action="{{ route('sewa-payment-registrations.generate') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="sewa_id" value="{{ $sewa->id }}">
                                        <button type="submit" class="btn btn-sm btn-action-link"><i class="icon-base bx bx-link me-1"></i>Link</button>
                                    </form>
                                    @endCanMenu
                                    @canMenu('manajemen_sewa.data_sewa', 'update')
                                    <a href="{{ route('sewas.edit', $sewa) }}" class="btn btn-sm btn-edit-fancy">
                                        <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                                    </a>
                                    @endCanMenu
                                    @canMenu('manajemen_sewa.data_sewa', 'delete')
                                    <form method="POST" action="{{ route('sewas.destroy', $sewa) }}" onsubmit="return confirm('Hapus sewa ini?')" class="d-inline">
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

        {{-- ═══ TAB PEMBAYARAN ═══ --}}
        <div class="tab-pane fade" id="panel-pembayaran" role="tabpanel">
            <div class="card-datatable table-responsive">
                <table class="table datatable" id="tbl-pembayaran-sewa" data-mobile-cols="2,5,6">
                    <thead>
                        <tr>
                            <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-pembayaran-sewa"></div></th>
                            <th data-orderable="false">#</th>
                            <th>Penghuni</th>
                            <th>Periode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembayarans as $pembayaran)
                        @php
                            $namaByr = $pembayaran->sewa->penghuni->nama ?? 'Unknown';
                            $partsByr = explode(' ', trim($namaByr));
                            $inisialByr = strtoupper(mb_substr($partsByr[0], 0, 1)) . (isset($partsByr[1]) ? strtoupper(mb_substr($partsByr[1], 0, 1)) : '');
                        @endphp
                        <tr>
                            <td><div class="form-check"><input type="checkbox" class="form-check-input"></div></td>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle {{ $avatarColors[$loop->index % 5] }}">{{ $inisialByr }}</span>
                                    </div>
                                    <div>
                                        <strong>{{ $namaByr }}</strong>
                                        <small class="d-block text-body-secondary">Kamar {{ $pembayaran->sewa->kamar->nomor ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ optional($pembayaran->periode)->format('m/Y') }}</td>
                            <td>Rp {{ number_format((float)$pembayaran->jumlah,0,',','.') }}</td>
                            <td>
                                @if($pembayaran->status === 'lunas')
                                    <span class="badge rounded-pill bg-label-success">Lunas</span>
                                @elseif($pembayaran->status === 'belum_lunas')
                                    <span class="badge rounded-pill bg-label-danger">Belum Lunas</span>
                                @else
                                    <span class="badge rounded-pill bg-label-secondary">{{ ucfirst(str_replace('_',' ',$pembayaran->status)) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 action-stack-mobile">
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
    </div>
</div>
@endsection

@section('scripts')
<style>
    @media (max-width: 767.98px) {
        #sewaTabs {
            width: 100%;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            scrollbar-width: none;
            margin-bottom: .35rem;
        }

        #sewaTabs::-webkit-scrollbar {
            display: none;
        }

        #sewaTabs .nav-link {
            font-size: .78rem;
            padding: .4rem .6rem;
        }

        .card-header .btn-add-sewa,
        .card-header .btn-add-pembayaran {
            min-height: 38px;
        }

        #tbl-sewa td:last-child,
        #tbl-pembayaran-sewa td:last-child {
            min-width: 98px;
        }

        #tbl-sewa .action-stack-mobile,
        #tbl-pembayaran-sewa .action-stack-mobile {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem !important;
            align-items: center;
            justify-content: flex-start;
        }

        #tbl-sewa .action-stack-mobile > a,
        #tbl-sewa .action-stack-mobile > form,
        #tbl-pembayaran-sewa .action-stack-mobile > a,
        #tbl-pembayaran-sewa .action-stack-mobile > form {
            margin: 0 !important;
        }
    }
</style>
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

    document.getElementById('tab-sewa').addEventListener('shown.bs.tab', function () {
        document.querySelector('.btn-add-sewa').classList.remove('d-none');
        document.querySelector('.btn-add-pembayaran').classList.add('d-none');
    });
    document.getElementById('tab-pembayaran').addEventListener('shown.bs.tab', function () {
        document.querySelector('.btn-add-sewa').classList.add('d-none');
        document.querySelector('.btn-add-pembayaran').classList.remove('d-none');
    });
</script>
@endsection

