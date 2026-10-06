<div class="card mb-4">
    <div class="card-body py-3">
        <div class="nav-align-top">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ request()->routeIs('profil-kost.profile') ? 'active' : '' }}" href="{{ route('profil-kost.profile') }}">
                        <i class="bx bx-buildings me-1"></i>Profil
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ request()->routeIs('kamar-tipe-hargas.*') ? 'active' : '' }}" href="{{ route('kamar-tipe-hargas.index') }}">
                        <i class="bx bx-category me-1"></i>Tipe Kamar
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ request()->routeIs('profil-kost.notification') ? 'active' : '' }}" href="{{ route('profil-kost.notification') }}">
                        <i class="bx bx-bell me-1"></i>Notifikasi
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
