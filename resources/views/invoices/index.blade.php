@php
$pageTitle = 'Invoice';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-file-blank"></i></div>
    <div>
        <h2>Invoice</h2>
        <p>Kelola dan kirim tagihan bulanan penghuni kost.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Invoice Penghuni</h5>
            <small class="text-body-secondary">Kelola tagihan bulanan penghuni.</small>
        </div>
        <div class="d-flex gap-2">
            @canMenu('keuangan.invoice', 'create')
            <form method="POST" action="{{ route('invoices.refresh-from-payments') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning"><i class="bx bx-refresh me-1"></i>Refresh Invoice dari Pembayaran</button>
            </form>
            @endCanMenu
            <button class="btn btn-outline-secondary"><i class="bx bx-export me-1"></i>Export</button>
            @canMenu('keuangan.invoice', 'create')
            <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Buat Invoice</a>
            @endCanMenu
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-invoice" data-mobile-cols="2,5,6,7">
            <thead>
                <tr>
                    <th><div class="form-check"><input type="checkbox" class="form-check-input" id="chkAll-invoice"></div></th>
                    <th data-orderable="false">#</th>
                    <th>Invoice</th>
                    <th>Periode</th>
                    <th>Jatuh Tempo</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $avatarColors = ['bg-label-primary','bg-label-success','bg-label-warning','bg-label-danger','bg-label-info']; @endphp
                @foreach($invoices as $invoice)
                @php
                    $nama = $invoice->penghuni->nama ?? 'Unknown';
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
                                <strong>{{ $invoice->nomor_invoice }}</strong>
                                <small class="d-block text-body-secondary">{{ $nama }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ optional($invoice->periode)->format('m/Y') }}</td>
                    <td>{{ optional($invoice->jatuh_tempo)->format('d/m/Y') }}</td>
                    <td>Rp {{ number_format((float)$invoice->jumlah_tagihan,0,',','.') }}</td>
                    <td>
                        @if($invoice->status === 'lunas')
                            <span class="badge rounded-pill bg-label-success">Lunas</span>
                        @elseif($invoice->status === 'dikirim')
                            <span class="badge rounded-pill bg-label-primary">Dikirim</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary">{{ ucfirst($invoice->status) }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-action-detail">
                                <i class="icon-base bx bx-detail me-1"></i>Detail
                            </a>
                            @canMenu('keuangan.invoice', 'update')
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-edit-fancy">
                                <i class="icon-base bx bx-edit-alt me-1"></i>Edit
                            </a>
                            @endCanMenu
                            @canMenu('keuangan.invoice', 'delete')
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice ini?')" class="d-inline">
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

