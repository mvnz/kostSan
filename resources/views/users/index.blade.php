@php
$pageTitle = 'Manajemen User';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-group"></i></div>
    <div>
        <h2>Manajemen User</h2>
        <p>Akun pengguna sistem dan pengaturan role akses.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="card-title mb-0">
            <h5 class="mb-1">Manajemen User</h5>
            <small class="text-body-secondary">Atur akun pengguna dan role akses.</small>
        </div>
        @canMenu('manajemen_akses.user', 'create')
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah User</a>
        @endCanMenu
    </div>

    <div class="card-datatable table-responsive">
        <table class="table datatable" id="tbl-users" data-mobile-cols="1,3,4">
            <thead>
                <tr>
                    <th data-orderable="false">#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role?->name ?: '-' }}</td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            @canMenu('manajemen_akses.user', 'update')
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-edit-fancy"><i class="icon-base bx bx-edit-alt me-1"></i>Edit</a>
                            @endCanMenu
                            @canMenu('manajemen_akses.user', 'delete')
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
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
