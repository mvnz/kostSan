@php
$pageTitle = 'Manajemen Role';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-shield-quarter"></i></div>
    <div>
        <h2>Manajemen Role</h2>
        <p>Atur peran pengguna dan hak akses menu sistem.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Manajemen Role</h5>
            <small class="text-body-secondary">Atur peran untuk akses sistem.</small>
        </div>
        @canMenu('manajemen_akses.role', 'create')
        <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah Role</a>
        @endCanMenu
    </div>

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-roles" data-mobile-cols="1,5">
            <thead>
                <tr>
                    <th data-orderable="false">#</th>
                    <th>Nama Role</th>
                    <th>Deskripsi</th>
                    <th>Akses Menu</th>
                    <th>Jumlah User</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $role->name }}</strong></td>
                    <td>{{ $role->description ?: '-' }}</td>
                    <td>
                        @if(!empty($role->menu_crud_summaries))
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($role->menu_crud_summaries as $label)
                                    <span class="badge bg-label-primary">{{ $label }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-body-secondary">-</span>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @canMenu('manajemen_akses.role', 'update')
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-edit-fancy"><i class="icon-base bx bx-edit-alt me-1"></i>Edit</a>
                            @endCanMenu
                            @canMenu('manajemen_akses.role', 'delete')
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Hapus role ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-action-danger"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
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
@endsection
