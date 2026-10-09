@php($pageTitle = 'Pilih Kamar')
@extends('layouts.app')

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('dashboard') }}" class="text-muted">Home</a> /
        <a href="{{ route('sewas.index') }}" class="text-muted">Sewa</a> /
    </span>
    Pilih Kamar
</h4>

<div class="card">
    <div class="card-body p-4">

        {{-- Stats bar --}}
        <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
            <div class="text-center">
                <span class="d-block fs-4 fw-bold text-success">{{ $counts['tersedia'] }}</span>
                <small class="text-body-secondary">Tersedia</small>
            </div>
            <div class="text-center">
                <span class="d-block fs-4 fw-bold text-danger">{{ $counts['terisi'] }}</span>
                <small class="text-body-secondary">Terisi</small>
            </div>
            <div class="text-center">
                <span class="d-block fs-4 fw-bold text-warning">{{ $counts['reservasi'] }}</span>
                <small class="text-body-secondary">Reservasi</small>
            </div>
            <div class="text-center">
                <span class="d-block fs-4 fw-bold" style="color:#a8aaae">{{ $counts['perbaikan'] }}</span>
                <small class="text-body-secondary">Perbaikan</small>
            </div>
            <div class="text-center">
                <span class="d-block fs-4 fw-bold" style="color:#696cff">{{ $kamars->count() }}</span>
                <small class="text-body-secondary">Total</small>
            </div>
        </div>

        {{-- Legend --}}
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#28c76f"></span><small>Tersedia (klik untuk pilih)</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#696cff"></span><small>Dipilih</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#ea5455"></span><small>Terisi</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box reservation-marker"></span><small>Ada reservasi terkonfirmasi</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#a8aaae"></span><small>Perbaikan</small>
            </div>
        </div>

        {{-- Cinema Screen --}}
        <div class="cinema-screen mb-5">
            <span>KAMAR TERSEDIA</span>
        </div>

        {{-- Seats Grid --}}
        <div class="seats-container mb-4">
            @forelse($kamars as $kamar)
            <div class="seat seat-{{ $kamar->status }}"
                 data-id="{{ $kamar->id }}"
                 data-nomor="{{ $kamar->nomor }}"
                 data-tipe="{{ $kamar->tipe }}"
                 data-harga="{{ (int) $kamar->harga_bulanan }}"
                 data-status="{{ $kamar->status }}"
                 title="{{ $kamar->nomor }} — {{ $kamar->tipe }} — Rp {{ number_format((float)$kamar->harga_bulanan,0,',','.') }}/bln — {{ ucfirst($kamar->status) }}{{ $kamar->confirmed_reservations_count ? ' — Ada reservasi terkonfirmasi' : '' }}"
                 @if($kamar->status === 'tersedia') onclick="selectSeat(this)" role="button" @endif>
                @if($kamar->confirmed_reservations_count)
                    <span class="reservation-badge" aria-label="Ada reservasi terkonfirmasi" title="Ada reservasi terkonfirmasi"><i class="bx bx-calendar-check"></i></span>
                @endif
                <span class="seat-number">{{ $kamar->nomor }}</span>
                <span class="seat-type">{{ $kamar->tipe }}</span>
                <span class="seat-icon">
                    @if($kamar->status === 'tersedia')
                        <i class="bx bx-home-smile"></i>
                    @elseif($kamar->status === 'terisi')
                        <i class="bx bx-user"></i>
                    @else
                        <i class="bx bx-wrench"></i>
                    @endif
                </span>
            </div>
            @empty
            <p class="text-center text-body-secondary w-100">Belum ada data kamar.</p>
            @endforelse
        </div>

        {{-- Info Panel --}}
        <div id="seat-info" class="seat-info-panel d-none mt-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary" id="info-initial">--</span>
                    </div>
                    <div>
                        <strong id="info-nomor">-</strong>
                        <small class="d-block text-body-secondary" id="info-tipe">-</small>
                    </div>
                </div>
                <div class="text-center">
                    <span class="fw-bold text-primary fs-5" id="info-harga">-</span>
                    <small class="d-block text-body-secondary">per bulan</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="cancelSeat()">
                        <i class="bx bx-x me-1"></i>Batal
                    </button>
                    <a href="#" id="btn-lanjutkan" class="btn btn-primary">
                        <i class="bx bx-home-plus me-1"></i>Sewa Kamar
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<style>
    .legend-box {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        display: inline-block;
        flex-shrink: 0;
    }
    .reservation-marker { background:#ff9f43; border-radius:50%; width:12px; height:12px; }
    .reservation-badge {
        position:absolute; top:-7px; right:-7px; width:24px; height:24px;
        display:flex; align-items:center; justify-content:center; border-radius:50%;
        background:#ff9f43; color:#fff; border:2px solid #fff; z-index:1;
    }

    /* ─── Cinema Screen ─── */
    .cinema-screen {
        position: relative;
        height: 44px;
        background: linear-gradient(to bottom, rgba(105,108,255,0.25), transparent);
        border-top: 4px solid #696cff;
        border-radius: 50% 50% 0 0 / 8px 8px 0 0;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 6px;
    }
    .cinema-screen span {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 3px;
        color: #696cff;
        text-transform: uppercase;
    }

    /* ─── Seats container ─── */
    .seats-container {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
    }

    /* ─── Seat base ─── */
    .seat {
        width: 88px;
        border-radius: 10px 10px 6px 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 6px 8px;
        gap: 2px;
        text-align: center;
        position: relative;
        transition: transform .18s, box-shadow .18s;
        /* Seat "leg" */
        box-shadow: 0 4px 0 0 rgba(0,0,0,.12);
    }
    .seat::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 20%;
        width: 60%;
        height: 6px;
        border-radius: 0 0 4px 4px;
        opacity: .6;
    }

    .seat-number { font-size: .8rem; font-weight: 700; line-height: 1; }
    .seat-type   { font-size: .65rem; opacity: .85; line-height: 1; }
    .seat-icon   { font-size: .9rem; margin-top: 2px; opacity: .9; }

    /* ─── Seat colors ─── */
    .seat-tersedia { background: #28c76f; color: #fff; cursor: pointer; }
    .seat-tersedia::after { background: #1fa855; }
    .seat-tersedia:hover  { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(40,199,111,.45), 0 4px 0 0 rgba(0,0,0,.12); }

    .seat-terisi    { background: #ea5455; color: #fff; cursor: not-allowed; opacity: .75; }
    .seat-terisi::after { background: #c73f40; }

    .seat-perbaikan { background: #a8aaae; color: #fff; cursor: not-allowed; opacity: .7; }
    .seat-perbaikan::after { background: #8e9094; }

    .seat-selected  { background: #696cff !important; color: #fff !important; cursor: pointer; opacity: 1; }
    .seat-selected::after { background: #4a4dc7 !important; }
    .seat-selected  { transform: translateY(-5px); box-shadow: 0 10px 28px rgba(105,108,255,.55), 0 4px 0 0 rgba(0,0,0,.12); }

    /* ─── Info panel ─── */
    .seat-info-panel {
        background: #f8f8ff;
        border: 1.5px solid #696cff;
        border-radius: 10px;
        padding: 1rem 1.5rem;
        animation: slideUp .25s ease;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
<script>
    let selectedEl = null;

    function selectSeat(el) {
        if (selectedEl && selectedEl !== el) {
            selectedEl.classList.remove('seat-selected');
            selectedEl.classList.add('seat-tersedia');
        }
        selectedEl = el;
        el.classList.remove('seat-tersedia');
        el.classList.add('seat-selected');

        const nomor = el.dataset.nomor;
        const tipe  = el.dataset.tipe;
        const harga = parseInt(el.dataset.harga, 10);
        const id    = el.dataset.id;

        const inisial = nomor.replace(/\s/g, '').substring(0, 2).toUpperCase();
        document.getElementById('info-initial').textContent = inisial;
        document.getElementById('info-nomor').textContent   = 'Kamar ' + nomor;
        document.getElementById('info-tipe').textContent    = tipe;
        document.getElementById('info-harga').textContent   = 'Rp ' + harga.toLocaleString('id-ID');
        document.getElementById('btn-lanjutkan').href       = '{{ route("sewas.create") }}?kamar_id=' + id;

        const panel = document.getElementById('seat-info');
        panel.classList.remove('d-none');
        setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
    }

    function cancelSeat() {
        if (selectedEl) {
            selectedEl.classList.remove('seat-selected');
            selectedEl.classList.add('seat-tersedia');
            selectedEl = null;
        }
        document.getElementById('seat-info').classList.add('d-none');
    }
</script>
@endsection
