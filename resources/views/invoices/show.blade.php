@php $pageTitle = 'Detail Invoice'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Detail Invoice</h2><p class="text-break">{{ $invoice->nomor_invoice }}</p></div>
<div class="card"><div class="card-body"><dl class="row">
<dt class="col-sm-3">Penghuni</dt><dd class="col-sm-9 text-break">{{ $invoice->penghuni?->nama ?? '-' }}</dd>
<dt class="col-sm-3">Periode</dt><dd class="col-sm-9">{{ $invoice->periode?->format('m/Y') }}</dd>
<dt class="col-sm-3">Jatuh tempo</dt><dd class="col-sm-9">{{ $invoice->jatuh_tempo?->format('d/m/Y') }}</dd>
<dt class="col-sm-3">Jumlah tagihan</dt><dd class="col-sm-9">Rp {{ number_format((float) $invoice->jumlah_tagihan, 2, ',', '.') }}</dd>
<dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($invoice->status) }}</dd>
<dt class="col-sm-3">Keterangan</dt><dd class="col-sm-9 text-break">{{ $invoice->keterangan ?: '-' }}</dd>
<dt class="col-sm-3">Sumber</dt><dd class="col-sm-9">@if($invoice->payment_id)Pembayaran #{{ $invoice->payment_id }} · Kamar {{ $invoice->payment?->sewa?->kamar?->nomor ?? '-' }}@else Invoice manual/legacy @endif</dd>
</dl>
@if($invoice->payment_id)<div class="alert alert-info">Invoice otomatis mengikuti pembayaran asal dan tidak dapat diedit terpisah.</div>@endif
<div class="d-flex flex-wrap gap-2"><a class="btn btn-secondary" href="{{ route('invoices.index') }}">Kembali ke Invoice</a>
@if($invoice->payment_id)
@canMenu('manajemen_sewa.data_sewa', 'view')<a class="btn btn-outline-primary" href="{{ route('pembayarans.show', $invoice->payment_id) }}">Pembayaran asal</a>@endCanMenu
@else
@canMenu('keuangan.invoice', 'update')<a class="btn btn-primary" href="{{ route('invoices.edit', $invoice) }}">Edit invoice</a>@endCanMenu
@endif
</div></div></div>
@endsection
