@php
$pageTitle = 'Profil Akun';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-user-circle"></i></div>
    <div>
        <h2>Profil Akun</h2>
        <p>Kelola informasi akun dan keamanan login Anda.</p>
    </div>
</div>

<div class="row g-4">

    {{-- Info Profil --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title mb-0">
                    <h5 class="mb-1">Informasi Akun</h5>
                    <small class="text-body-secondary">Nama dan email yang digunakan untuk login.</small>
                </div>
            </div>
            <div class="card-body pt-4">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Avatar inisial --}}
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:#f5f8ff;border:1px solid #dde8fb;">
                    <div style="width:56px;height:56px;border-radius:.9rem;background:linear-gradient(135deg,#295fcb,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.97rem;color:#1d3a7a;">{{ auth()->user()->name }}</div>
                        <div style="font-size:.82rem;color:#7a8fad;">{{ auth()->user()->email }}</div>
                        @if(auth()->user()->role)
                            <span class="badge bg-label-primary mt-1">{{ auth()->user()->role->name }}</span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Ubah Password --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title mb-0">
                    <h5 class="mb-1">Ubah Password</h5>
                    <small class="text-body-secondary">Gunakan password yang kuat dan berbeda dari sebelumnya.</small>
                </div>
            </div>
            <div class="card-body pt-4">

                @if(session('success_password'))
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                        <i class="bx bx-check-circle me-2"></i>{{ session('success_password') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Password Saat Ini</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="cur_pw"
                                class="form-control @error('current_password') is-invalid @enderror"
                                placeholder="••••••••" autocomplete="current-password">
                            <button type="button" class="pw-toggle" data-target="cur_pw" tabindex="-1">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                        @error('current_password')<div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="new_pw"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Min. 8 karakter" autocomplete="new-password">
                            <button type="button" class="pw-toggle" data-target="new_pw" tabindex="-1">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                        @error('password')<div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" id="conf_pw"
                                class="form-control"
                                placeholder="Ulangi password baru" autocomplete="new-password">
                            <button type="button" class="pw-toggle" data-target="conf_pw" tabindex="-1">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-lock-alt me-1"></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<style>
    .pw-toggle {
        position: absolute;
        right: .85rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #8ca3c0;
        cursor: pointer;
        padding: 0;
        font-size: 1.1rem;
        line-height: 1;
    }
    .pw-toggle:hover { color: #4f72b5; }
    .form-control { padding-right: 2.5rem; }
</style>

<script>
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bx bx-show';
        } else {
            input.type = 'password';
            icon.className = 'bx bx-hide';
        }
    });
});
</script>
@endsection
