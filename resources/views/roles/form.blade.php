@extends('layouts.app')

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light"><a href="{{ route('dashboard') }}" class="text-muted">Home</a> / <a href="{{ route('roles.index') }}" class="text-muted">Role</a> /</span>
    {{ $isEdit ? 'Edit Role' : 'Tambah Role' }}
</h4>

<style>
    .role-form-shell {
        border: 1px solid #e7ecf6;
        box-shadow: 0 12px 28px rgba(31, 51, 89, 0.08);
        overflow: hidden;
    }
    .role-form-head {
        background: linear-gradient(135deg, #f3f6ff 0%, #eef9ff 100%);
        border-bottom: 1px solid #e2e8f5;
        padding: 1rem 1.25rem;
    }
    .role-form-head h5 {
        margin: 0;
        font-weight: 700;
        color: #32435f;
    }
    .role-form-head p {
        margin: .3rem 0 0;
        color: #6f809a;
        font-size: .85rem;
    }
    .permission-tools {
        background: #f8faff;
        border: 1px solid #e1e9f7;
        border-radius: .75rem;
        padding: .75rem;
    }
    .permission-group {
        border: 1px solid #e4eaf7;
        border-radius: .85rem;
        background: #fff;
        box-shadow: 0 6px 16px rgba(18, 45, 89, 0.05);
        height: 100%;
    }
    .permission-group-head {
        padding: .75rem .9rem;
        border-bottom: 1px solid #edf1fa;
        background: #fbfcff;
    }
    .permission-group-tools {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .45rem;
    }
    .permission-item {
        border: 1px solid #edf1fa;
        border-radius: .65rem;
        padding: .65rem .7rem;
        background: #fff;
    }
    .permission-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .permission-check {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid #dce5f5;
        border-radius: 999px;
        padding: .25rem .6rem;
        padding-left: .6rem;
        background: #f8fbff;
        min-height: 32px;
    }
    .permission-check .form-check-input {
        float: none;
        margin: 0;
        margin-left: 0;
        flex-shrink: 0;
    }
    .permission-check .form-check-label {
        font-size: .78rem;
        color: #556882;
        font-weight: 600;
        margin: 0;
        line-height: 1;
    }
    .role-form-actions {
        border-top: 1px solid #edf1fa;
        padding-top: 1rem;
    }
    @media (max-width: 575.98px) {
        .permission-tools {
            padding: .65rem;
        }
        .permission-group-head {
            align-items: flex-start !important;
        }
        .permission-group-tools {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>

<div class="card role-form-shell">
    <div class="role-form-head d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h5>{{ $isEdit ? 'Perbarui Role' : 'Role Baru' }}</h5>
            <p>Atur identitas role dan hak CRUD per menu dengan tampilan yang lebih ringkas.</p>
        </div>
        <span class="badge bg-label-primary px-3 py-2" id="total-selected-badge">0 Akses Dipilih</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('roles.update', $role) : route('roles.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label">Nama Role</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $role->description) }}" placeholder="Contoh: Admin penuh akses">
            </div>

            @php
                $selectedPermissions = old('menu_permissions', $role->menu_permissions ?? []);
            @endphp
            <div class="mb-4">
                <div class="permission-tools d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <label class="form-label mb-1 fw-semibold">Akses CRUD per Menu</label>
                        <small class="text-body-secondary d-block">Aksi di setiap menu sudah disesuaikan dengan kebutuhan modul, jadi tidak semua menu punya Tambah, Edit, dan Hapus.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllCrud(true)"><i class="bx bx-select-multiple me-1"></i>Pilih Semua</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCrud(false)"><i class="bx bx-reset me-1"></i>Kosongkan</button>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach($menuGroups as $groupName => $menus)
                        @php
                            $groupSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $groupName));
                        @endphp
                        <div class="col-12 col-lg-6">
                            <div class="permission-group">
                                <div class="permission-group-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0">{{ $groupName }}</h6>
                                    <div class="permission-group-tools">
                                        <span class="badge bg-label-info" id="group-count-{{ $groupSlug }}">0 dipilih</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleGroupCrud('{{ $groupSlug }}', true)">Semua</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleGroupCrud('{{ $groupSlug }}', false)">Reset</button>
                                    </div>
                                </div>

                                <div class="p-3">

                                    @foreach($menus as $menuKey => $menuLabel)
                                        @php
                                            $selectedActions = collect($selectedPermissions[$menuKey] ?? [])->map(fn($v) => (string) $v)->all();
                                            $availableActions = $menuActionOptions[$menuKey] ?? ['view'];
                                        @endphp
                                        <div class="permission-item mb-2">
                                            <div class="fw-semibold mb-2">{{ $menuLabel }}</div>
                                            <div class="permission-actions">
                                                @foreach($availableActions as $action)
                                                    <div class="form-check permission-check">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input js-crud-box"
                                                            data-group="{{ $groupSlug }}"
                                                            id="perm-{{ str_replace(['.', '_'], '-', $menuKey) }}-{{ $action }}"
                                                            name="menu_permissions[{{ $menuKey }}][]"
                                                            value="{{ $action }}"
                                                            {{ in_array($action, $selectedActions, true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="perm-{{ str_replace(['.', '_'], '-', $menuKey) }}-{{ $action }}">
                                                            {{ $crudActionLabels[$action] ?? ucfirst($action) }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('menu_permissions')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('menu_permissions.*')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('menu_permissions.*.*')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2 role-form-actions">
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Role' }}</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAllCrud(checked) {
        document.querySelectorAll('.js-crud-box').forEach((el) => {
            el.checked = checked;
        });
        updateCrudCounters();
    }

    function toggleGroupCrud(groupSlug, checked) {
        document.querySelectorAll('.js-crud-box[data-group="' + groupSlug + '"]').forEach((el) => {
            el.checked = checked;
        });
        updateCrudCounters();
    }

    function updateCrudCounters() {
        const all = Array.from(document.querySelectorAll('.js-crud-box'));
        const selected = all.filter((el) => el.checked).length;
        const totalBadge = document.getElementById('total-selected-badge');
        if (totalBadge) {
            totalBadge.textContent = selected + ' Akses Dipilih';
        }

        const groups = [...new Set(all.map((el) => el.dataset.group).filter(Boolean))];
        groups.forEach((group) => {
            const groupChecked = all.filter((el) => el.dataset.group === group && el.checked).length;
            const el = document.getElementById('group-count-' + group);
            if (el) {
                el.textContent = groupChecked + ' dipilih';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-crud-box').forEach((el) => {
            el.addEventListener('change', updateCrudCounters);
        });
        updateCrudCounters();
    });
</script>
@endsection
