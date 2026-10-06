@php
$pageTitle = 'Data Lantai';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-building-house"></i></div>
    <div>
        <h2>Data Lantai</h2>
        <p>Nomor dan nama lantai untuk peta denah hunian kost.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Master Data Lantai</h5>
            <small class="text-body-secondary">Kelola nomor dan nama lantai untuk denah sewa kamar.</small>
        </div>
        @canMenu('master_data.data_lantai', 'create')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddFloor">
            <i class="bx bx-plus me-1"></i>Tambah Lantai
        </button>
        @endCanMenu
    </div>

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-floor" data-mobile-cols="1,2,4">
            <thead>
                <tr>
                    <th data-orderable="false">#</th>
                    <th>Nomor Lantai</th>
                    <th>Nama Lantai</th>
                    <th>Jumlah Kamar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($floors as $floor)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="badge bg-label-primary">{{ $floor->number }}</span></td>
                    <td><strong>{{ $floor->name }}</strong></td>
                    <td>{{ (int) $floor->kamar_count }}</td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @canMenu('master_data.data_lantai', 'update')
                            <button
                                type="button"
                                class="btn btn-sm btn-edit-fancy"
                                onclick="openEditFloorModal({{ $floor->id }}, {{ $floor->number }}, @js($floor->name))">
                                <i class="bx bx-edit-alt me-1"></i>Edit
                            </button>
                            @endCanMenu

                            @canMenu('master_data.data_lantai', 'delete')
                            <form method="POST" action="{{ route('kamar-floors.destroy', $floor) }}" onsubmit="return confirm('Hapus {{ addslashes($floor->name) }}?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger" {{ (int) $floor->kamar_count > 0 ? 'disabled title=Tidak bisa dihapus karena masih dipakai kamar' : '' }}>
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

<div class="modal fade" id="modalAddFloor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-layer-plus me-2 text-primary"></i>Tambah Lantai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('kamar-floors.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lantai</label>
                        <input type="text" name="name" class="form-control" maxlength="100" placeholder="Contoh: Lantai 3" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Nomor Lantai (opsional)</label>
                        <input type="number" name="number" class="form-control" min="1" max="255" placeholder="Kosongkan untuk nomor otomatis">
                    </div>
                    <small class="text-body-secondary">Jika dikosongkan, sistem akan memakai nomor berikutnya.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditFloor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-edit-alt me-2 text-info"></i>Edit Lantai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-edit-floor" method="POST" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Lantai</label>
                        <input type="number" id="edit-floor-number" name="number" class="form-control" min="1" max="255" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Nama Lantai</label>
                        <input type="text" id="edit-floor-name" name="name" class="form-control" maxlength="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bx bx-save me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const updateFloorRouteTemplate = '{{ route("kamar-floors.update", ["kamarFloor" => "__FLOOR_ID__"]) }}';

    function openEditFloorModal(id, number, name) {
        document.getElementById('form-edit-floor').action = updateFloorRouteTemplate.replace('__FLOOR_ID__', id);
        document.getElementById('edit-floor-number').value = number;
        document.getElementById('edit-floor-name').value = name || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditFloor'));
        modal.show();
    }
</script>
@endsection
