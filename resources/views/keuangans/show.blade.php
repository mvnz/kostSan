@php $pageTitle = 'Detail Keuangan'; @endphp
@extends('layouts.app')
@section('content')
<div class="page-head"><h2>Detail Keuangan #{{ $keuangan->id }}</h2><p>Rincian transaksi dan bukti pencatatan.</p></div>
<div class="card"><div class="card-body">
<dl class="row">
<dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">{{ $keuangan->tanggal?->format('d/m/Y') }}</dd>
<dt class="col-sm-3">Jenis</dt><dd class="col-sm-9">{{ $keuangan->jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran' }}</dd>
<dt class="col-sm-3">Kategori</dt><dd class="col-sm-9">{{ $keuangan->kategori }}</dd>
<dt class="col-sm-3">Jumlah</dt><dd class="col-sm-9">Rp {{ number_format((float) $keuangan->jumlah, 2, ',', '.') }}</dd>
<dt class="col-sm-3">Deskripsi</dt><dd class="col-sm-9 text-break">{{ $keuangan->deskripsi }}</dd>
<dt class="col-sm-3">Sumber</dt><dd class="col-sm-9">@if($keuangan->manually_linked_at)Tautan rekonsiliasi manual · Pembayaran #{{ $keuangan->payment_id }} · {{ $keuangan->manually_linked_at->format('d/m/Y H:i') }}@elseif($keuangan->payment_id)Sumber otomatis · Pembayaran #{{ $keuangan->payment_id }}@elseif($keuangan->reversalSource)Pembalikan otomatis · Pembayaran #{{ $keuangan->reversalSource->payment_id }}@else Pencatatan manual @endif</dd>
<dt class="col-sm-3">Bukti</dt><dd class="col-sm-9">@if($keuangan->bukti_path)<a href="{{ route('secure-files.show', ['path' => $keuangan->bukti_path]) }}" target="_blank" rel="noopener">Lihat bukti</a>@else Belum ada bukti @endif</dd>
</dl>
@if($keuangan->manually_linked_at)<div class="alert alert-warning">Pemasukan manual ini ditautkan melalui rekonsiliasi dan tidak dapat diedit/dihapus selama tertaut. Tautan dapat dilepas dengan alasan; payment, pemasukan, dan jejak audit tetap dipertahankan.</div>
@elseif($keuangan->payment_id || $keuangan->reversalSource)<div class="alert alert-info">Transaksi otomatis mengikuti pembayaran asal dan tidak dapat diubah atau dihapus. Koreksi harus menjaga riwayat transaksi.</div>@endif
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-secondary" href="{{ route('keuangans.index') }}">Kembali ke Keuangan</a>
@if($keuangan->payment_id || $keuangan->reversalSource)
@canMenu('manajemen_sewa.data_sewa', 'view')<a class="btn btn-outline-primary" href="{{ route('pembayarans.show', $keuangan->payment_id ?? $keuangan->reversalSource->payment_id) }}">Pembayaran asal</a>@endCanMenu
@else
@canMenu('keuangan.data_keuangan', 'update')<a class="btn btn-primary" href="{{ route('keuangans.edit', $keuangan) }}">Edit transaksi</a>@endCanMenu
@endif
</div></div></div>
@if($keuangan->manually_linked_at && auth()->user()->hasMenuPermission('keuangan.data_keuangan', 'update') && auth()->user()->hasMenuPermission('manajemen_sewa.data_sewa', 'update'))
<div class="card mt-3"><div class="card-body"><h5>Lepas tautan rekonsiliasi</h5><p>Gunakan hanya jika pemasukan yang dipilih keliru. Data transaksi tidak dihapus dan tindakan dicatat.</p><form method="POST" action="{{ route('keuangans.reconciliation.detach', $keuangan->payment_id) }}" onsubmit="return confirm('Lepas tautan manual ini? Pembayaran akan kembali muncul pada rekonsiliasi.')">@csrf @method('DELETE')<label class="form-label" for="unlink-reason">Alasan koreksi</label><input class="form-control" id="unlink-reason" name="reason" minlength="10" maxlength="500" required><button class="btn btn-warning mt-2" type="submit">Lepas tautan dengan audit</button></form></div></div>
@endif
@if($keuangan->linkAudits->isNotEmpty())
<div class="card mt-3"><div class="card-body"><h5>Riwayat tautan pembayaran</h5><div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Operator</th><th>Alasan</th></tr></thead><tbody>@foreach($keuangan->linkAudits->sortBy('id') as $audit)<tr><td>{{ $audit->created_at?->format('d/m/Y H:i') }}</td><td>{{ $audit->action === 'linked' ? 'Ditautkan' : 'Dilepas' }}</td><td>{{ $audit->user?->name ?? 'Pengguna dihapus' }}</td><td class="text-break">{{ $audit->reason }}</td></tr>@endforeach</tbody></table></div></div></div>
@endif
@endsection
