@php
$pageTitle = $invoice->exists ? 'Edit Invoice' : 'Buat Invoice';
$pageSubtitle = 'Form pembuatan invoice penghuni.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-file-blank"></i></div>
    <div>
        <h2>{{ $invoice->exists ? 'Edit Invoice' : 'Buat Invoice' }}</h2>
        <p>Isi detail periode dan jumlah tagihan.</p>
    </div>
</div>
<div class="card"><div class="card-body"><form method="POST" action="{{ $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store') }}">
@csrf @if($invoice->exists) @method('PUT') @endif
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Penghuni</label><select name="penghuni_id" class="form-select" required><option value="">Pilih penghuni</option>@foreach($penghunis as $penghuni)<option value="{{ $penghuni->id }}" @selected((string) old('penghuni_id', $invoice->penghuni_id) === (string) $penghuni->id)>{{ $penghuni->nama }}</option>@endforeach</select></div><div class="col-md-6 mb-3"><label class="form-label">Periode</label><input type="date" name="periode" class="form-control" value="{{ old('periode', optional($invoice->periode)->format('Y-m-d')) }}" required></div></div>
<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Jatuh Tempo</label><input type="date" name="jatuh_tempo" class="form-control" value="{{ old('jatuh_tempo', optional($invoice->jatuh_tempo)->format('Y-m-d')) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Jumlah Tagihan</label><input type="number" step="0.01" min="0" name="jumlah_tagihan" class="form-control" value="{{ old('jumlah_tagihan', $invoice->jumlah_tagihan) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select" required>@foreach(['draft','terkirim','lunas'] as $status)<option value="{{ $status }}" @selected(old('status', $invoice->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div></div>
<div class="mb-3"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $invoice->keterangan) }}</textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Simpan</button><a href="{{ route('invoices.index') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection
