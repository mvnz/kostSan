@php $pageTitle = 'Detail Pembayaran'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Detail Pembayaran #{{ $pembayaran->id }}</h2><p>Telusuri pembayaran dan pencatatan keuangan asal.</p></div>
<div class="card"><div class="card-body"><dl class="row">
@if(session('sewa_payment_link'))<div class="alert alert-success"><strong>Link bukti pembayaran:</strong> <a class="text-break" href="{{ session('sewa_payment_link') }}" target="_blank" rel="noopener">{{ session('sewa_payment_link') }}</a><div class="small mt-1">Salin dan kirim hanya kepada penghuni terkait. Link berlaku tujuh hari dan satu kali pakai.</div></div>@endif
<dt class="col-sm-3">Penghuni</dt><dd class="col-sm-9 text-break">{{ $pembayaran->sewa?->penghuni?->nama ?? '-' }}</dd>
<dt class="col-sm-3">Kamar</dt><dd class="col-sm-9">{{ $pembayaran->sewa?->kamar?->nomor ?? '-' }}</dd>
<dt class="col-sm-3">Periode</dt><dd class="col-sm-9">{{ $pembayaran->periode?->format('m/Y') }}</dd>
@if($pembayaran->coverage_start)<dt class="col-sm-3">Masa yang ditagih</dt><dd class="col-sm-9">{{ $pembayaran->coverage_start->format('d/m/Y') }} sampai sebelum {{ $pembayaran->coverage_end?->format('d/m/Y') }}</dd>@endif
<dt class="col-sm-3">Tanggal bayar</dt><dd class="col-sm-9">{{ $pembayaran->tanggal_bayar?->format('d/m/Y') ?? 'Belum tercatat' }}</dd>
<dt class="col-sm-3">Metode</dt><dd class="col-sm-9">{{ $pembayaran->metode }}</dd>
<dt class="col-sm-3">Jumlah</dt><dd class="col-sm-9">Rp {{ number_format((float) $pembayaran->jumlah, 2, ',', '.') }}</dd>
<dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($pembayaran->reversal)Dibalik penuh (riwayat lunas dipertahankan)@else{{ $pembayaran->status === 'lunas' ? 'Lunas / disetujui' : 'Belum lunas / menunggu persetujuan' }}@endif</dd>
<dt class="col-sm-3">Keterangan</dt><dd class="col-sm-9 text-break">{{ $pembayaran->keterangan ?: '-' }}</dd>
<dt class="col-sm-3">Bukti</dt><dd class="col-sm-9">@if($pembayaran->bukti_pembayaran_path)<a href="{{ route('secure-files.show', ['path' => $pembayaran->bukti_pembayaran_path]) }}" target="_blank" rel="noopener">Lihat bukti</a>@else Belum ada bukti @endif</dd>
</dl>
@if($pembayaran->reversal)<div class="alert alert-warning">Pembalikan penuh dicatat pada {{ $pembayaran->reversal->reversal_date?->format('d/m/Y') }}. Alasan: {{ $pembayaran->reversal->reason }}. Pembayaran, invoice, dan pemasukan asli dipertahankan.</div>
@elseif($pembayaran->status === 'lunas')<div class="alert alert-info">Pembayaran disetujui tidak dapat diubah atau dihapus. Jika dana perlu dikembalikan/dibatalkan, buat pembalikan penuh agar riwayat tetap utuh.</div>@endif
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-secondary" href="{{ route('pembayarans.index') }}">Kembali ke Pembayaran</a>
@if($pembayaran->status !== 'lunas')
@canMenu('manajemen_sewa.data_sewa', 'update')
<a class="btn btn-primary" href="{{ route('pembayarans.edit', $pembayaran) }}">Edit pembayaran</a>
<form method="POST" action="{{ route('payment-registrations.generate', $pembayaran) }}" onsubmit="return confirm('Buat link satu kali untuk penghuni mengirim metode dan bukti pembayaran tagihan ini? Link lama untuk tagihan ini akan dinonaktifkan.')">@csrf<button class="btn btn-outline-primary" type="submit">Buat link bukti</button></form>
<form method="POST" action="{{ route('pembayarans.approve', $pembayaran) }}" onsubmit="return confirm('Setujui pembayaran ini dan catat pemasukan?')">@csrf<button class="btn btn-success" type="submit">Approve pembayaran</button></form>
@endCanMenu
@endif
@if($pembayaran->ledgerEntry)
@canMenu('keuangan.data_keuangan', 'view')<a class="btn btn-outline-primary" href="{{ route('keuangans.show', $pembayaran->ledgerEntry) }}">Pemasukan Keuangan #{{ $pembayaran->ledgerEntry->id }}</a>@endCanMenu
@endif
</div></div></div>
@if($pembayaran->status === 'lunas' && !$pembayaran->reversal)
@canMenu('manajemen_sewa.data_sewa', 'update')
<div class="card mt-3"><div class="card-body"><h5>Pembalikan penuh</h5><p class="text-body-secondary">Gunakan hanya bila seluruh pembayaran dibatalkan atau dikembalikan. Sistem menambah pengeluaran penyeimbang; transaksi asli tidak dihapus.</p>
<form method="POST" action="{{ route('pembayarans.reverse', $pembayaran) }}" class="row g-2" onsubmit="return confirm('Catat pembalikan penuh? Tindakan ini tidak dapat diedit atau dihapus.')">@csrf
<div class="col-md-3"><label class="form-label" for="reversal-date">Tanggal</label><input class="form-control" id="reversal-date" type="date" name="reversal_date" value="{{ old('reversal_date', now()->toDateString()) }}" required></div>
<div class="col-md-7"><label class="form-label" for="reversal-reason">Alasan</label><input class="form-control" id="reversal-reason" name="reason" maxlength="500" value="{{ old('reason') }}" required></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-danger w-100" type="submit">Balikkan penuh</button></div>
</form></div></div>
@endCanMenu
@endif
@endsection
