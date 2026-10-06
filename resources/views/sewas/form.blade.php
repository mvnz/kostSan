@php
$pageTitle = $sewa->exists ? 'Edit Sewa' : 'Tambah Sewa';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx {{ $sewa->exists ? 'bx-edit' : 'bx-plus-circle' }}"></i></div>
    <div>
        <h2>{{ $sewa->exists ? 'Edit Sewa' : 'Tambah Sewa' }}</h2>
        <p>{{ $sewa->exists ? 'Perbarui data sewa dan biaya bulanan penghuni.' : 'Buat kontrak sewa baru untuk penghuni kost.' }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ $sewa->exists ? route('sewas.update', $sewa) : route('sewas.store') }}" enctype="multipart/form-data">
            @csrf
            @if($sewa->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Kamar</label>
                    <select name="kamar_id" id="kamar_id" class="form-select" required>
                        <option value="">Pilih kamar</option>
                        @foreach($kamars as $kamar)
                        <option value="{{ $kamar->id }}"
                            data-harga="{{ (int) $kamar->harga_bulanan }}"
                            data-status="{{ $kamar->status }}"
                        @selected((string) $selectedKamarId === (string) $kamar->id)>
                            {{ $kamar->nomor }} - {{ $kamar->tipe }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Penghuni</label>
                    <select name="penghuni_id" class="form-select" required>
                        <option value="">Pilih penghuni</option>
                        @foreach($penghunis as $penghuni)
                        <option value="{{ $penghuni->id }}" @selected((string) old('penghuni_id', $sewa->penghuni_id) === (string) $penghuni->id)>
                            {{ $penghuni->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control"
                        value="{{ old('tanggal_masuk', optional($sewa->tanggal_masuk)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Lama Sewa</label>
                    <div class="input-group">
                        <select id="lama_sewa" class="form-select">
                            @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected($i === $lamaSewa)>{{ $i }} Bulan</option>
                            @endfor
                        </select>
                    </div>
                    <small class="text-body-secondary">Pilih durasi sewa</small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Tanggal Keluar</label>
                    <input type="date" name="tanggal_keluar" id="tanggal_keluar" class="form-control"
                        value="{{ old('tanggal_keluar', optional($sewa->tanggal_keluar)->format('Y-m-d')) }}">
                    <small class="text-body-secondary">Otomatis dari tanggal masuk + lama sewa</small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" id="status_sewa" class="form-select" required>
                        @foreach(['aktif','selesai','menunggak'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $sewa->status ?? 'aktif') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Pricing section --}}
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Harga Kamar</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="harga_kamar_display" class="form-control bg-light"
                            value="{{ number_format($selectedHarga, 0, ',', '.') }}" readonly>
                    </div>
                    <small class="text-body-secondary">Otomatis dari kamar dipilih</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Biaya Tambahan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="biaya_tambahan" class="form-control"
                            min="0" step="1000" value="{{ $biayaTambahan }}" placeholder="0">
                    </div>
                    <small class="text-body-secondary">Biaya ekstra di luar harga kamar</small>
                </div>
                {{-- hidden field that actually submits --}}
                <input type="hidden" name="biaya_bulanan" id="biaya_bulanan_hidden"
                    value="{{ $selectedHarga + $biayaTambahan }}">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Uang Jaminan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="1" min="0" name="uang_jaminan" class="form-control"
                            value="{{ old('uang_jaminan', $sewa->uang_jaminan ?? 0) }}">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Cara Pembayaran</label>
                    <select name="cara_pembayaran" class="form-select">
                        <option value="">Pilih cara pembayaran</option>
                        @foreach(['Tunai','Transfer Bank','QRIS','E-Wallet'] as $cp)
                        <option value="{{ $cp }}" @selected(old('cara_pembayaran', $sewa->cara_pembayaran) === $cp)>{{ $cp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Bukti Pembayaran</label>
                    <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*,.pdf">
                    <small class="text-body-secondary">Format: JPG, PNG, PDF. Maks 2MB.</small>
                    @if($sewa->exists && $sewa->bukti_pembayaran)
                    <div class="mt-2">
                        <small class="text-body-secondary">File saat ini: </small>
                        <a href="{{ route('secure-files.show', ['path' => $sewa->bukti_pembayaran]) }}" target="_blank" class="small">
                            <i class="bx bx-file me-1"></i>Lihat Bukti
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Catatan</label>
                <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $sewa->catatan) }}</textarea>
            </div>

            {{-- Total summary --}}
            <div class="d-flex align-items-center justify-content-between bg-primary bg-opacity-10 border border-primary rounded px-4 py-3 mb-4">
                <div>
                    <div class="text-body-secondary" style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Biaya / Bulan</div>
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                        <span class="text-body-secondary fw-semibold" style="font-size:1rem">Rp</span>
                        <span id="total_display" class="text-primary fw-bold" style="font-size:2rem;line-height:1">{{ number_format($selectedHarga + $biayaTambahan, 0, ',', '.') }}</span>
                    </div>
                    <small class="text-body-secondary">Harga kamar + biaya tambahan</small>
                </div>
                <i class="bx bx-money text-primary" style="font-size:3rem;opacity:.3"></i>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('sewas.index') }}" class="btn btn-outline-secondary">Kembali</a>
                @if($sewa->exists)
                <a href="{{ route('sewas.kontrak', $sewa) }}" target="_blank" class="btn btn-outline-danger ms-auto">
                    <i class="bx bx-file-pdf me-1"></i>Cetak Kontrak
                </a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let hargaKamar = {{ $selectedHarga }};

    // Base date for extending: use existing tanggal_keluar (already in DB) or tanggal_masuk for new sewas
    const initialKeluar = document.getElementById('tanggal_keluar').value;
    let baseKeluar = initialKeluar || null;

    function calcTanggalKeluar() {
        const bulan = parseInt(document.getElementById('lama_sewa').value, 10);
        if (!bulan) return;

        // Use existing tanggal_keluar as base (for edit/extend); fall back to tanggal_masuk for new sewas
        const base = baseKeluar || document.getElementById('tanggal_masuk').value;
        if (!base) return;

        const [year, month, date] = base.split('-').map(Number);
        const d = new Date(year, month - 1 + bulan, 1);
        const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        d.setDate(Math.min(date, lastDay));
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        document.getElementById('tanggal_keluar').value = `${y}-${m}-${day}`;
    }

    // When tanggal_masuk changes manually, reset base to masuk date
    document.getElementById('tanggal_masuk').addEventListener('change', function () {
        baseKeluar = null;
        calcTanggalKeluar();
    });
    document.getElementById('lama_sewa').addEventListener('change', calcTanggalKeluar);

    function recalc() {
        const tambahan = parseFloat(document.getElementById('biaya_tambahan').value) || 0;
        const total = hargaKamar + tambahan;

        document.getElementById('total_display').textContent = total.toLocaleString('id-ID');
        document.getElementById('biaya_bulanan_hidden').value = total;
    }

    document.getElementById('kamar_id').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        hargaKamar = parseFloat(opt.dataset.harga) || 0;
        document.getElementById('harga_kamar_display').value = hargaKamar.toLocaleString('id-ID');
        recalc();

        // Auto-set sewa status based on kamar availability
        const kamarStatus = opt.dataset.status || '';
        const statusSelect = document.getElementById('status_sewa');
        if (kamarStatus && kamarStatus !== 'tersedia') {
            statusSelect.value = 'selesai';
        } else if (kamarStatus === 'tersedia') {
            statusSelect.value = 'aktif';
        }
    });

    document.getElementById('biaya_tambahan').addEventListener('input', recalc);

    // Init on load
    recalc();
</script>
@endsection
