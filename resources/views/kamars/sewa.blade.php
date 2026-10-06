@php
$pageTitle = 'Sewa Kamar';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-home-circle"></i></div>
    <div>
        <h2>Sewa Kamar</h2>
        <p>Pilih kamar untuk mengelola sewa dan pembayaran.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Sewa Kamar</h5>
            <small class="text-body-secondary">Pilih kamar untuk mengelola sewa.</small>
        </div>
    </div>
    <div class="card-body p-4">

        @if(session('registration_link'))
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="alert">
                <div>
                    <strong>Link pendaftaran baru:</strong>
                    <div><a href="{{ session('registration_link') }}" target="_blank">{{ session('registration_link') }}</a></div>
                    <small class="text-body-secondary">Kirim link ini ke calon penyewa untuk isi form. Setelah dikirim, data sewa dibuat otomatis.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-copy-link="{{ session('registration_link') }}">
                    <i class="bx bx-copy me-1"></i>Copy
                </button>
            </div>
        @endif

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
                <span class="legend-box" style="background:#28c76f"></span><small>Tersedia</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#ea5455"></span><small>Terisi</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#ff9f43"></span><small>Reservasi</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background:#a8aaae"></span><small>Perbaikan</small>
            </div>
        </div>

        @php
            $defaultLayoutByNomor = [
                '105' => ['floor' => 1, 'col' => 1, 'row' => 1],
                '104' => ['floor' => 1, 'col' => 1, 'row' => 2],
                '103' => ['floor' => 1, 'col' => 1, 'row' => 3],
                '102' => ['floor' => 1, 'col' => 1, 'row' => 4],
                '101' => ['floor' => 1, 'col' => 1, 'row' => 5],
                '110' => ['floor' => 1, 'col' => 5, 'row' => 1],
                '109' => ['floor' => 1, 'col' => 5, 'row' => 2],
                '100' => ['floor' => 1, 'col' => 5, 'row' => 2],
                '108' => ['floor' => 1, 'col' => 5, 'row' => 3],
                '107' => ['floor' => 1, 'col' => 5, 'row' => 4],
                '106' => ['floor' => 1, 'col' => 5, 'row' => 5],
                '111' => ['floor' => 1, 'col' => 4, 'row' => 4],
                '206' => ['floor' => 2, 'col' => 1, 'row' => 1],
                '205' => ['floor' => 2, 'col' => 1, 'row' => 2],
                '204' => ['floor' => 2, 'col' => 1, 'row' => 3],
                '203' => ['floor' => 2, 'col' => 1, 'row' => 4],
                '202' => ['floor' => 2, 'col' => 1, 'row' => 5],
                '201' => ['floor' => 2, 'col' => 1, 'row' => 6],
                '217' => ['floor' => 2, 'col' => 2, 'row' => 1],
                '208' => ['floor' => 2, 'col' => 3, 'row' => 5],
                '207' => ['floor' => 2, 'col' => 3, 'row' => 6],
                '216' => ['floor' => 2, 'col' => 5, 'row' => 5],
                '215' => ['floor' => 2, 'col' => 5, 'row' => 6],
                '214' => ['floor' => 2, 'col' => 7, 'row' => 1],
                '213' => ['floor' => 2, 'col' => 7, 'row' => 2],
                '212' => ['floor' => 2, 'col' => 7, 'row' => 3],
                '211' => ['floor' => 2, 'col' => 7, 'row' => 4],
                '210' => ['floor' => 2, 'col' => 7, 'row' => 5],
                '209' => ['floor' => 2, 'col' => 7, 'row' => 6],
            ];

            $kamarsPosisi = $kamars->map(function ($item) use ($defaultLayoutByNomor) {
                $nomor = trim((string) $item->nomor);
                $default = $defaultLayoutByNomor[$nomor] ?? null;

                $item->resolved_floor = (int) ($item->layout_floor ?? ($default['floor'] ?? 0));
                $item->resolved_col = (int) ($item->layout_col ?? ($default['col'] ?? 0));
                $item->resolved_row = (int) ($item->layout_row ?? ($default['row'] ?? 0));

                return $item;
            });

            $knownFloorNumbers = $floors->pluck('number')->map(fn($n) => (int) $n)->all();

            $floorsData = $floors->map(function ($floor) use ($kamarsPosisi) {
                $floorNumber = (int) $floor->number;

                $floorKamars = $kamarsPosisi
                    ->filter(fn($item) => $item->resolved_floor === $floorNumber && $item->resolved_col > 0 && $item->resolved_row > 0)
                    ->sortBy(fn($item) => ($item->resolved_row * 100) + $item->resolved_col)
                    ->values();

                return [
                    'floor' => $floor,
                    'kamars' => $floorKamars,
                    'max_col' => max(1, (int) ($floorKamars->max('resolved_col') ?? 0)),
                    'max_row' => max(1, (int) ($floorKamars->max('resolved_row') ?? 0)),
                ];
            })->values();

            $kamarTanpaPosisi = $kamarsPosisi
                ->filter(fn($item) => $item->resolved_floor < 1 || $item->resolved_col < 1 || $item->resolved_row < 1 || !in_array($item->resolved_floor, $knownFloorNumbers, true))
                ->values();
        @endphp

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h6 class="mb-0">Denah per Lantai</h6>
            <small class="text-body-secondary">Kelola tambah, edit, dan hapus lantai dari menu Master Data > Data Lantai.</small>
        </div>

        <div class="denah-wrapper mb-4">
            @foreach($floorsData as $floorData)
                @php
                    $floor = $floorData['floor'];
                @endphp
                <section class="floor-card">
                    <div class="floor-head">
                        <h6 class="mb-0">{{ $floor->name }}</h6>
                        <small class="text-body-secondary">Nomor lantai: {{ $floor->number }} • Posisi bisa dicustom dari tombol Atur Posisi pada setiap kamar.</small>
                    </div>
                    <div class="floor-map {{ $floorData['kamars']->isEmpty() ? 'floor-map-empty' : '' }}" style="--cols:{{ $floorData['max_col'] }}; --rows:{{ $floorData['max_row'] }};">
                        @forelse($floorData['kamars'] as $kamar)
                            @php
                                $aktifSewa = $kamar->sewas->firstWhere('status', 'aktif');
                                $sewaHistory = $kamar->sewas->map(fn($s) => [
                                    'id'          => $s->id,
                                    'penghuni'    => $s->penghuni?->nama ?? 'Unknown',
                                    'masuk'       => optional($s->tanggal_masuk)->format('d/m/Y'),
                                    'keluar'      => optional($s->tanggal_keluar)->format('d/m/Y'),
                                    'biaya'       => (int) $s->biaya_bulanan,
                                    'status'      => $s->status,
                                ]);
                            @endphp
                            <div class="seat seat-{{ $kamar->status }}"
                                    style="grid-column: {{ $kamar->resolved_col }}; grid-row: {{ $kamar->resolved_row }};"
                                 data-id="{{ $kamar->id }}"
                                 data-layout-floor="{{ $kamar->resolved_floor }}"
                                 data-layout-col="{{ $kamar->resolved_col }}"
                                 data-layout-row="{{ $kamar->resolved_row }}"
                                 data-sewa-masuk="{{ optional($aktifSewa?->tanggal_masuk)->format('Y-m-d') }}"
                                 data-sewa-keluar="{{ optional($aktifSewa?->tanggal_keluar)->format('Y-m-d') }}"
                                 data-sewa-biaya="{{ (int) ($aktifSewa?->biaya_bulanan ?? $kamar->harga_bulanan) }}"
                                 data-harga-kamar="{{ (int) $kamar->harga_bulanan }}"
                                 data-sewas='{{ json_encode($sewaHistory) }}'
                                 onclick="openKamar({{ $kamar->id }}, '{{ addslashes($kamar->nomor) }}', '{{ addslashes($kamar->tipe) }}', {{ (int) $kamar->harga_bulanan }}, '{{ $kamar->status }}')"
                                 role="button"
                                 title="{{ $kamar->nomor }} — {{ $kamar->tipe }} — Rp {{ number_format((float)$kamar->harga_bulanan,0,',','.') }}/bln — {{ ucfirst($kamar->status) }}">
                                <span class="seat-number">{{ $kamar->nomor }}</span>
                                <span class="seat-type">{{ $kamar->tipe }}</span>
                                <span class="seat-icon">
                                    @if($kamar->status === 'tersedia')
                                        <i class="bx bx-home-smile"></i>
                                    @elseif($kamar->status === 'terisi')
                                        <i class="bx bx-user"></i>
                                    @elseif($kamar->status === 'reservasi')
                                        <i class="bx bx-calendar-check"></i>
                                    @else
                                        <i class="bx bx-wrench"></i>
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="floor-empty-text text-body-secondary small">Belum ada kamar yang diposisikan di lantai ini.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        @if($kamarTanpaPosisi->isNotEmpty())
            <div class="extra-rooms-card mt-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h6 class="mb-0">Kamar Tanpa Posisi Layout</h6>
                    <small class="text-body-secondary">Klik kamar lalu gunakan tombol Atur Posisi untuk meletakkan ke denah.</small>
                </div>
                <div class="extra-room-list">
                    @foreach($kamarTanpaPosisi as $kamar)
                        @php
                            $aktifSewa = $kamar->sewas->firstWhere('status', 'aktif');
                            $sewaHistory = $kamar->sewas->map(fn($s) => [
                                'id'          => $s->id,
                                'penghuni'    => $s->penghuni?->nama ?? 'Unknown',
                                'masuk'       => optional($s->tanggal_masuk)->format('d/m/Y'),
                                'keluar'      => optional($s->tanggal_keluar)->format('d/m/Y'),
                                'biaya'       => (int) $s->biaya_bulanan,
                                'status'      => $s->status,
                            ]);
                        @endphp
                        <div class="seat seat-{{ $kamar->status }}"
                             data-id="{{ $kamar->id }}"
                             data-layout-floor="{{ $kamar->resolved_floor }}"
                             data-layout-col="{{ $kamar->resolved_col }}"
                             data-layout-row="{{ $kamar->resolved_row }}"
                             data-sewa-masuk="{{ optional($aktifSewa?->tanggal_masuk)->format('Y-m-d') }}"
                             data-sewa-keluar="{{ optional($aktifSewa?->tanggal_keluar)->format('Y-m-d') }}"
                             data-sewa-biaya="{{ (int) ($aktifSewa?->biaya_bulanan ?? $kamar->harga_bulanan) }}"
                             data-harga-kamar="{{ (int) $kamar->harga_bulanan }}"
                             data-sewas='{{ json_encode($sewaHistory) }}'
                             onclick="openKamar({{ $kamar->id }}, '{{ addslashes($kamar->nomor) }}', '{{ addslashes($kamar->tipe) }}', {{ (int)$kamar->harga_bulanan }}, '{{ $kamar->status }}')"
                             role="button"
                             title="{{ $kamar->nomor }} — {{ $kamar->tipe }} — Rp {{ number_format((float)$kamar->harga_bulanan,0,',','.') }}/bln — {{ ucfirst($kamar->status) }}">
                            <span class="seat-number">{{ $kamar->nomor }}</span>
                            <span class="seat-type">{{ $kamar->tipe }}</span>
                            <span class="seat-icon">
                                @if($kamar->status === 'tersedia')
                                    <i class="bx bx-home-smile"></i>
                                @elseif($kamar->status === 'terisi')
                                    <i class="bx bx-user"></i>
                                @elseif($kamar->status === 'reservasi')
                                    <i class="bx bx-calendar-check"></i>
                                @else
                                    <i class="bx bx-wrench"></i>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Info Panel --}}
        <div id="kamar-info" class="seat-info-panel d-none mt-3">
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
                    <span id="info-status" class="badge rounded-pill mt-1">-</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="closePanel()">
                        <i class="bx bx-x me-1"></i>Tutup
                    </button>
                    <button type="button" class="btn btn-outline-info" id="btn-layout" onclick="openLayoutModal()">
                        <i class="bx bx-move me-1"></i>Atur Posisi
                    </button>
                    @canMenu('manajemen_sewa.sewa_kamar', 'create')
                    <a href="#" id="btn-sewa-kamar" class="btn btn-primary d-none">
                        <i class="bx bx-home-circle me-1"></i>Sewa Kamar
                    </a>
                    <form id="form-generate-link" method="POST" action="{{ route('penghuni-registrations.generate') }}" class="d-inline d-none">
                        @csrf
                        <input type="hidden" name="kamar_id" id="input-generate-kamar-id">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bx bx-link-alt me-1"></i>Link Pendaftaran
                        </button>
                    </form>
                    <form id="form-selesai" method="POST" action="#" onsubmit="return confirm('Selesaikan sewa aktif kamar ini?')" class="d-inline d-none">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-check-circle me-1"></i>Selesaikan Sewa
                        </button>
                    </form>
                    <button type="button" id="btn-perpanjang" class="btn btn-warning d-none" onclick="openPerpanjangModal()">
                        <i class="bx bx-calendar-plus me-1"></i>Perpanjang Sewa
                    </button>
                    @endCanMenu
                </div>
            </div>
        </div>

        {{-- Riwayat Sewa Panel --}}
        <div id="riwayat-panel" class="d-none mt-4">
            <h6 class="fw-semibold mb-3"><i class="bx bx-history me-1 text-primary"></i>Riwayat Sewa — <span id="riwayat-kamar-label">-</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="tbl-riwayat">
                    <thead>
                        <tr style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#566a7f">
                            <th>#</th>
                            <th>Penghuni</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Biaya/Bulan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="riwayat-tbody"></tbody>
                </table>
                <p id="riwayat-empty" class="text-center text-body-secondary small d-none">Belum ada riwayat sewa untuk kamar ini.</p>
            </div>
        </div>

    </div>
</div>

{{-- Modal Atur Posisi Layout --}}
<div class="modal fade" id="modalLayout" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-move me-2 text-info"></i>Atur Posisi Kamar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-layout" method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <p class="text-body-secondary mb-3" id="layout-kamar-label">-</p>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Lantai</label>
                            <select name="layout_floor" id="layout-floor" class="form-select" required>
                                @foreach($floors as $floorOption)
                                    <option value="{{ $floorOption->number }}">{{ $floorOption->name }} ({{ $floorOption->number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Kolom</label>
                            <input type="number" name="layout_col" id="layout-col" class="form-control" min="1" max="30" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Baris</label>
                            <input type="number" name="layout_row" id="layout-row" class="form-control" min="1" max="30" required>
                        </div>
                    </div>
                    <small class="text-body-secondary">Posisi ini langsung dipakai untuk menampilkan denah sewa kamar.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bx bx-save me-1"></i>Simpan Posisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Perpanjang Sewa --}}
<div class="modal fade" id="modalPerpanjang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-calendar-plus me-2 text-warning"></i>Perpanjang Sewa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-perpanjang" method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <p class="text-body-secondary mb-3" id="modal-kamar-label">-</p>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Harga Kamar</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="modal-harga-kamar" class="form-control bg-light" readonly>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Biaya Tambahan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="modal-biaya-tambahan" class="form-control" min="0" step="1000" value="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between bg-warning bg-opacity-10 border border-warning rounded px-3 py-2 mb-3">
                        <div>
                            <div class="text-body-secondary" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Biaya <span id="modal-label-bulan">1 Bulan</span></div>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="text-body-secondary fw-semibold">Rp</span>
                                <span id="modal-total-display" class="fw-bold text-warning" style="font-size:1.5rem;line-height:1">0</span>
                            </div>
                            <small class="text-body-secondary">(Rp <span id="modal-total-per-bulan">0</span> / bulan)</small>
                        </div>
                        <i class="bx bx-money" style="font-size:2rem;color:#ff9f43;opacity:.4"></i>
                    </div>
                    <input type="hidden" name="biaya_bulanan" id="modal-biaya-hidden" value="0">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Perpanjang Berapa Bulan</label>
                        <div class="input-group">
                            <select id="modal-lama-bulan" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ $i }} Bulan</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 bg-light rounded px-3 py-2 mb-1">
                        <i class="bx bx-calendar-check text-success fs-5"></i>
                        <div>
                            <div class="text-body-secondary" style="font-size:.75rem">Tanggal Keluar Baru</div>
                            <strong id="modal-tanggal-keluar-display" class="text-success">-</strong>
                        </div>
                    </div>
                    <input type="hidden" name="tanggal_keluar" id="input-tanggal-keluar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-check me-1"></i>Simpan Perpanjangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
    .legend-box {
        width: 18px; height: 18px;
        border-radius: 4px;
        display: inline-block;
        flex-shrink: 0;
    }
    .denah-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 1rem;
        align-items: start;
    }
    .floor-card {
        border: 1px solid #e9ecf2;
        border-radius: 12px;
        background: linear-gradient(180deg, #fcfdff 0%, #f9fbff 100%);
        padding: 1rem;
    }
    .floor-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .9rem;
    }
    .floor-map {
        --cell-w: 68px;
        --cell-h: 68px;
        display: grid;
        grid-template-columns: repeat(var(--cols), var(--cell-w));
        grid-template-rows: repeat(var(--rows), var(--cell-h));
        justify-content: start;
        gap: .55rem;
        background: #ffffff;
        border: 1px dashed #dfe3ec;
        border-radius: 10px;
        padding: .85rem;
        overflow: auto;
    }
    .floor-map.floor-map-empty {
        display: block;
        min-height: 120px;
        overflow: hidden;
    }
    .floor-empty-text {
        max-width: 280px;
        line-height: 1.45;
    }
    .corridor {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed #c6d3ff;
        background: repeating-linear-gradient(-45deg, #eff3ff, #eff3ff 8px, #f7f9ff 8px, #f7f9ff 16px);
        border-radius: 8px;
        color: #7584a0;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 700;
    }
    .extra-rooms-card {
        border-top: 1px dashed #d8dce7;
        padding-top: 1rem;
    }
    .extra-room-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .extra-room-list .seat {
        --cell-w: 68px;
        --cell-h: 68px;
    }
    .seat {
        width: var(--cell-w);
        height: var(--cell-h);
        border-radius: 10px 10px 6px 6px;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        padding: 8px 5px 7px; gap: 1px;
        text-align: center; position: relative;
        transition: transform .18s, box-shadow .18s;
        box-shadow: 0 4px 0 0 rgba(0,0,0,.12);
        cursor: pointer;
    }
    .seat::after {
        content: ''; position: absolute;
        bottom: -10px; left: 20%; width: 60%; height: 6px;
        border-radius: 0 0 4px 4px; opacity: .6;
    }
    .seat-number { font-size: .8rem; font-weight: 700; line-height: 1; }
    .seat-type   { font-size: .65rem; opacity: .85; line-height: 1; }
    .seat-icon   { font-size: .9rem; margin-top: 2px; opacity: .9; }

    .seat-empty {
        background: #f5f6fa;
        color: #8e97ab;
        border: 1px dashed #cfd5e5;
        box-shadow: none;
        cursor: default;
    }
    .seat-empty::after { display: none; }

    .seat-tersedia { background: #28c76f; color: #fff; }
    .seat-tersedia::after { background: #1fa855; }
    .seat-tersedia:hover  { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(40,199,111,.45), 0 4px 0 0 rgba(0,0,0,.12); }

    .seat-terisi    { background: #ea5455; color: #fff; }
    .seat-terisi::after { background: #c73f40; }
    .seat-terisi:hover  { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(234,84,85,.45), 0 4px 0 0 rgba(0,0,0,.12); }

    .seat-reservasi { background: #ff9f43; color: #fff; }
    .seat-reservasi::after { background: #e08830; }
    .seat-reservasi:hover  { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(255,159,67,.45), 0 4px 0 0 rgba(0,0,0,.12); }

    .seat-perbaikan { background: #a8aaae; color: #fff; opacity: .8; }
    .seat-perbaikan::after { background: #8e9094; }
    .seat-perbaikan:hover  { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(168,170,174,.45), 0 4px 0 0 rgba(0,0,0,.12); }

    .seat-active { outline: 3px solid #696cff; outline-offset: 2px; transform: translateY(-5px); }

    .seat-info-panel {
        background: #f8f8ff; border: 1.5px solid #696cff;
        border-radius: 10px; padding: 1rem 1.5rem;
        animation: slideUp .25s ease;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 767.98px) {
        .denah-wrapper {
            grid-template-columns: 1fr;
        }
        .floor-map {
            --cell-w: 56px;
            --cell-h: 56px;
            grid-template-columns: repeat(var(--cols), var(--cell-w));
            grid-template-rows: repeat(var(--rows), var(--cell-h));
            gap: .45rem;
            padding: .65rem;
        }
        .extra-room-list .seat {
            --cell-w: 56px;
            --cell-h: 56px;
        }
        .seat-number { font-size: .72rem; }
        .seat-type { font-size: .6rem; }

        #tbl-riwayat {
            table-layout: fixed;
            width: 100%;
        }

        #tbl-riwayat th,
        #tbl-riwayat td {
            white-space: normal;
            word-break: break-word;
            font-size: .78rem;
            padding: .45rem .5rem;
        }

        /* Mobile riwayat: keep only important columns (Penghuni, Biaya/Bulan, Status). */
        #tbl-riwayat th:nth-child(1),
        #tbl-riwayat td:nth-child(1),
        #tbl-riwayat th:nth-child(3),
        #tbl-riwayat td:nth-child(3),
        #tbl-riwayat th:nth-child(4),
        #tbl-riwayat td:nth-child(4),
        #tbl-riwayat th:nth-child(7),
        #tbl-riwayat td:nth-child(7) {
            display: none;
        }
    }
</style>
<script>
    let activeSeat = null;
    let activeKamarId = null;
    const layoutRouteTemplate = '{{ route("kamars.update-layout", ["kamar" => "__KAMAR_ID__"]) }}';
    const defaultLayoutFloor = '{{ (string) ($floors->first()->number ?? 1) }}';

    const statusConfig = {
        tersedia:  { label: 'Tersedia',  cls: 'bg-label-success' },
        terisi:    { label: 'Terisi',    cls: 'bg-label-danger'  },
        reservasi: { label: 'Reservasi', cls: 'bg-label-warning' },
        perbaikan: { label: 'Perbaikan', cls: 'bg-label-secondary' },
    };

    function openKamar(id, nomor, tipe, harga, status) {
        activeKamarId = id;
        if (activeSeat) activeSeat.classList.remove('seat-active');
        activeSeat = document.querySelector('.seat[data-id="' + id + '"]');
        if (activeSeat) activeSeat.classList.add('seat-active');

        const cfg = statusConfig[status] || { label: ucfirst(status), cls: 'bg-label-secondary' };
        const inisial = nomor.replace(/\s/g, '').substring(0, 2).toUpperCase();

        document.getElementById('info-initial').textContent = inisial;
        document.getElementById('info-nomor').textContent   = 'Kamar ' + nomor;
        document.getElementById('info-tipe').textContent    = tipe;
        document.getElementById('info-harga').textContent   = 'Rp ' + harga.toLocaleString('id-ID');

        const badge = document.getElementById('info-status');
        badge.textContent = cfg.label;
        badge.className   = 'badge rounded-pill mt-1 ' + cfg.cls;

        document.getElementById('form-selesai').action = '/kamars/' + id + '/selesai-sewa';
        document.getElementById('form-perpanjang').action = '/kamars/' + id + '/perpanjang-sewa';
        document.getElementById('form-layout').action = layoutRouteTemplate.replace('__KAMAR_ID__', id);

        const sewaKamarBtn  = document.getElementById('btn-sewa-kamar');
        const generateForm = document.getElementById('form-generate-link');
        const selesaiForm   = document.getElementById('form-selesai');
        const perpanjangBtn = document.getElementById('btn-perpanjang');
        const isTersedia = status === 'tersedia';
        const isTerisi   = status === 'terisi';

        sewaKamarBtn.href = '{{ route("sewas.create") }}?kamar_id=' + id;
        sewaKamarBtn.classList.toggle('d-none', !isTersedia);
        generateForm.classList.toggle('d-none', !isTersedia);
        document.getElementById('input-generate-kamar-id').value = id;
        selesaiForm.classList.toggle('d-none', !isTerisi);
        perpanjangBtn.classList.toggle('d-none', !isTerisi);

        const panel = document.getElementById('kamar-info');
        panel.classList.remove('d-none');

        // Render riwayat sewa
        const sewas = JSON.parse(activeSeat.dataset.sewas || '[]');
        renderRiwayat(id, nomor, sewas);

        setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
    }

    function openLayoutModal() {
        if (!activeSeat || !activeKamarId) return;

        document.getElementById('layout-kamar-label').textContent =
            document.getElementById('info-nomor').textContent + ' — ' + document.getElementById('info-tipe').textContent;

        const floorSelect = document.getElementById('layout-floor');
        const targetFloor = activeSeat.dataset.layoutFloor || defaultLayoutFloor;
        floorSelect.value = floorSelect.querySelector('option[value="' + targetFloor + '"]')
            ? targetFloor
            : defaultLayoutFloor;
        document.getElementById('layout-col').value = activeSeat.dataset.layoutCol || '1';
        document.getElementById('layout-row').value = activeSeat.dataset.layoutRow || '1';

        const modal = new bootstrap.Modal(document.getElementById('modalLayout'));
        modal.show();
    }

    const statusBadge = {
        aktif:    '<span class="badge rounded-pill bg-label-success">Aktif</span>',
        selesai:  '<span class="badge rounded-pill bg-label-secondary">Selesai</span>',
        menunggak:'<span class="badge rounded-pill bg-label-warning">Menunggak</span>',
    };

    function renderRiwayat(kamarId, nomor, sewas) {
        document.getElementById('riwayat-kamar-label').textContent = 'Kamar ' + nomor;
        const tbody = document.getElementById('riwayat-tbody');
        const empty = document.getElementById('riwayat-empty');
        const rPanel = document.getElementById('riwayat-panel');
        tbody.innerHTML = '';
        rPanel.classList.remove('d-none');
        if (!sewas.length) {
            empty.classList.remove('d-none');
            return;
        }
        empty.classList.add('d-none');
        sewas.forEach((s, i) => {
            const badge = statusBadge[s.status] || '<span class="badge rounded-pill bg-label-secondary">' + s.status + '</span>';
            tbody.innerHTML += `<tr>
                <td>${i + 1}</td>
                <td>${s.penghuni}</td>
                <td>${s.masuk || '-'}</td>
                <td>${s.keluar || '-'}</td>
                <td>Rp ${s.biaya.toLocaleString('id-ID')}</td>
                <td>${badge}</td>
                <td><a href="/sewas/${s.id}/edit" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit-alt me-1"></i>Edit</a></td>
            </tr>`;
        });
    }

    function openPerpanjangModal() {
        if (!activeSeat) return;
        const baseDate   = activeSeat.dataset.sewaKeluar  || activeSeat.dataset.sewaMasuk || '';
        const hargaKamar = parseInt(activeSeat.dataset.hargaKamar, 10) || 0;
        const sewaBiaya  = parseInt(activeSeat.dataset.sewaBiaya, 10)  || 0;
        const tambahan   = Math.max(0, sewaBiaya - hargaKamar);

        document.getElementById('modal-kamar-label').textContent =
            document.getElementById('info-nomor').textContent + ' — ' + document.getElementById('info-tipe').textContent;
        document.getElementById('modal-harga-kamar').value    = hargaKamar.toLocaleString('id-ID');
        document.getElementById('modal-biaya-tambahan').value = tambahan;
        document.getElementById('modal-biaya-tambahan').dataset.hargaKamar = hargaKamar;
        document.getElementById('modal-lama-bulan').dataset.baseDate   = baseDate;
        document.getElementById('modal-lama-bulan').dataset.hargaKamar = hargaKamar;
        document.getElementById('modal-lama-bulan').value = 1;
        updateModalTotal(hargaKamar, tambahan, 1);
        updateModalTanggalKeluar(baseDate, 1);

        const modal = new bootstrap.Modal(document.getElementById('modalPerpanjang'));
        modal.show();
    }

    function updateModalTanggalKeluar(masuk, bulan) {
        if (!masuk) { document.getElementById('modal-tanggal-keluar-display').textContent = '-'; return; }
        const d = new Date(masuk);
        d.setMonth(d.getMonth() + bulan);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const isoDate = `${y}-${m}-${day}`;
        const display = `${day}/${m}/${y}`;
        document.getElementById('input-tanggal-keluar').value = isoDate;
        document.getElementById('modal-tanggal-keluar-display').textContent = display;
    }

    document.getElementById('modal-lama-bulan').addEventListener('change', function () {
        const baseDate = this.dataset.baseDate || '';
        const bulan = parseInt(this.value, 10);
        const hk  = parseInt(this.dataset.hargaKamar, 10) || 0;
        const tmb = parseFloat(document.getElementById('modal-biaya-tambahan').value) || 0;
        updateModalTanggalKeluar(baseDate, bulan);
        updateModalTotal(hk, tmb, bulan);
    });

    function updateModalTotal(hargaKamar, tambahan, bulan) {
        bulan = bulan || parseInt(document.getElementById('modal-lama-bulan').value, 10) || 1;
        const perBulan = hargaKamar + tambahan;
        const total    = perBulan * bulan;
        document.getElementById('modal-label-bulan').textContent    = bulan + ' Bulan';
        document.getElementById('modal-total-per-bulan').textContent = perBulan.toLocaleString('id-ID');
        document.getElementById('modal-total-display').textContent   = total.toLocaleString('id-ID');
        document.getElementById('modal-biaya-hidden').value          = perBulan; // store per-bulan for sewa record
    }

    document.getElementById('modal-biaya-tambahan').addEventListener('input', function () {
        const hk    = parseInt(this.dataset.hargaKamar, 10) || 0;
        const tmb   = parseFloat(this.value) || 0;
        const bulan = parseInt(document.getElementById('modal-lama-bulan').value, 10) || 1;
        updateModalTotal(hk, tmb, bulan);
    });

    function closePanel() {
        if (activeSeat) { activeSeat.classList.remove('seat-active'); activeSeat = null; }
        activeKamarId = null;
        document.getElementById('kamar-info').classList.add('d-none');
        document.getElementById('riwayat-panel').classList.add('d-none');
    }

    function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

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
</script>
@endsection

