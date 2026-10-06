@extends('layouts.app')

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light"><a href="{{ route('dashboard') }}" class="text-muted">Home</a> / <a href="{{ route('users.index') }}" class="text-muted">User</a> /</span>
    {{ $isEdit ? 'Edit User' : 'Tambah User' }}
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <option value="">- Pilih Role -</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password {{ $isEdit ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }}>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control" {{ $isEdit ? '' : 'required' }}>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan User' }}</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
