@php
$pageTitle = 'Tipe Kamar';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-category"></i></div>
    <div>
        <h2>Tipe Kamar</h2>
        <p>Kelola tipe kamar beserta harga sewa untuk durasi 1, 3, 6, dan 12 bulan.</p>
    </div>
</div>

@include('profil-kost._submenu')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Pengaturan Tipe Kamar</h5>
            <small class="text-body-secondary">Harga per tipe kamar dipakai sebagai acuan harga di form Kamar (Master Data).</small>
        </div>
        @canMenu('pengaturan.tipe_kamar', 'create')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddTipe">
            <i class="bx bx-plus me-1"></i>Tambah Tipe Kamar
        </button>
        @endCanMenu
    </div>

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-tipe-kamar" data-mobile-cols="1,2,6">
            <thead>
                <tr>
                    <th data-orderable="false">#</th>
                    <th>Tipe Kamar</th>
                    <th>Harga 1 Bulan</th>
                    <th>Harga 3 Bulan</th>
                    <th>Harga 6 Bulan</th>
                    <th>Harga 12 Bulan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tipeHargas as $tipeHarga)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $tipeHarga->tipe }}</strong></td>
                    <td>Rp {{ number_format($tipeHarga->harga_1_bulan, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($tipeHarga->harga_3_bulan, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($tipeHarga->harga_6_bulan, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($tipeHarga->harga_12_bulan, 0, ',', '.') }}</td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @canMenu('pengaturan.tipe_kamar', 'update')
                            <button
                                type="button"
                                class="btn btn-sm btn-edit-fancy"
                                onclick="openEditTipeModal({{ $tipeHarga->id }}, @js($tipeHarga->tipe), {{ (float) $tipeHarga->harga_1_bulan }}, {{ (float) $tipeHarga->harga_3_bulan }}, {{ (float) $tipeHarga->harga_6_bulan }}, {{ (float) $tipeHarga->harga_12_bulan }})">
                                <i class="bx bx-edit-alt me-1"></i>Edit
                            </button>
                            @endCanMenu

                            @canMenu('pengaturan.tipe_kamar', 'delete')
                            <form method="POST" action="{{ route('kamar-tipe-hargas.destroy', $tipeHarga) }}" data-confirm-message="{{ 'Hapus tipe '.$tipeHarga->tipe.'?' }}" onsubmit="return confirm(this.dataset.confirmMessage)" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </button>
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

<div class="modal fade" id="modalAddTipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-plus-circle me-2 text-primary"></i>Tambah Tipe Kamar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('kamar-tipe-hargas.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Tipe Kamar</label>
                        <input type="text" name="tipe" class="form-control" maxlength="100" placeholder="Contoh: Standar, VIP" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 1 Bulan</label>
                            <input type="number" name="harga_1_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 3 Bulan</label>
                            <input type="number" name="harga_3_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 6 Bulan</label>
                            <input type="number" name="harga_6_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 12 Bulan</label>
                            <input type="number" name="harga_12_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditTipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-edit-alt me-2 text-info"></i>Edit Tipe Kamar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-edit-tipe" method="POST" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Tipe Kamar</label>
                        <input type="text" id="edit-tipe-nama" name="tipe" class="form-control" maxlength="100" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 1 Bulan</label>
                            <input type="number" id="edit-tipe-harga-1" name="harga_1_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 3 Bulan</label>
                            <input type="number" id="edit-tipe-harga-3" name="harga_3_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 6 Bulan</label>
                            <input type="number" id="edit-tipe-harga-6" name="harga_6_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga 12 Bulan</label>
                            <input type="number" id="edit-tipe-harga-12" name="harga_12_bulan" class="form-control" min="0" step="1000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const updateTipeRouteTemplate = '{{ route("kamar-tipe-hargas.update", ["kamarTipeHarga" => "__TIPE_ID__"]) }}';

    function openEditTipeModal(id, tipe, harga1, harga3, harga6, harga12) {
        document.getElementById('form-edit-tipe').action = updateTipeRouteTemplate.replace('__TIPE_ID__', id);
        document.getElementById('edit-tipe-nama').value = tipe;
        document.getElementById('edit-tipe-harga-1').value = harga1;
        document.getElementById('edit-tipe-harga-3').value = harga3;
        document.getElementById('edit-tipe-harga-6').value = harga6;
        document.getElementById('edit-tipe-harga-12').value = harga12;

        const modal = new bootstrap.Modal(document.getElementById('modalEditTipe'));
        modal.show();
    }
</script>
@endsection
