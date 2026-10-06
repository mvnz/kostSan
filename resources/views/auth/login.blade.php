<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login — {{ $appBrandName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Boxicons only — no Sneat layout CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" />
    <!-- Bootstrap 5 for utilities -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Public Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f2547 0%, #143364 40%, #1f5ca8 75%, #3b82f6 100%);
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 999px;
            pointer-events: none;
        }
        body::before {
            width: 420px;
            height: 420px;
            background: rgba(255,255,255,.06);
            top: -120px;
            right: -120px;
        }
        body::after {
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,.04);
            bottom: -80px;
            left: -80px;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Brand */
        .auth-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,.18);
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            backdrop-filter: blur(6px);
            margin-bottom: .75rem;
        }

        .auth-brand-name {
            font-size: 2rem;
            font-weight: 700;
            color: #ffd370;
            letter-spacing: -.03em;
            line-height: 1;
            margin: 0;
        }

        .auth-brand-tagline {
            font-size: .85rem;
            color: rgba(255,255,255,.7);
            margin-top: .3rem;
        }

        /* Card */
        .auth-card {
            background: #fff;
            border-radius: 1.25rem;
            padding: 2.25rem 2rem;
            box-shadow: 0 24px 60px rgba(10, 25, 60, .35);
        }

        .auth-card-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e3a6e;
            margin: 0 0 .35rem;
        }

        .auth-card-subtitle {
            font-size: .88rem;
            color: #7a8fad;
            margin: 0 0 1.75rem;
        }

        /* Form */
        .form-label {
            font-size: .84rem;
            font-weight: 600;
            color: #516079;
            margin-bottom: .35rem;
        }

        .form-control {
            border-color: #d4dce8;
            border-radius: .65rem;
            padding: .65rem .9rem;
            font-size: .9rem;
            color: #2c3e5a;
            transition: border-color .15s, box-shadow .15s;
        }

        .form-control:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 .18rem rgba(105,108,255,.18);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: .9rem;
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

        .password-toggle:hover { color: #4f72b5; }

        .btn-login {
            width: 100%;
            padding: .75rem;
            font-size: .97rem;
            font-weight: 700;
            border: none;
            border-radius: .75rem;
            background: linear-gradient(135deg, #295fcb 0%, #3b82f6 100%);
            color: #fff;
            box-shadow: 0 8px 20px rgba(47, 103, 212, .35);
            cursor: pointer;
            transition: all .18s ease;
            letter-spacing: .02em;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1f52b7 0%, #2f70df 100%);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(39, 96, 197, .4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-check-input:checked {
            background-color: #696cff;
            border-color: #696cff;
        }

        .form-check-label {
            font-size: .84rem;
            color: #516079;
        }

        .invalid-feedback {
            font-size: .82rem;
        }

        .auth-error-box {
            background: #fff5f5;
            border: 1px solid #f5c6cb;
            border-radius: .65rem;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: .86rem;
            color: #842029;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
        }

        .auth-error-box i { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: .82rem;
            color: rgba(255,255,255,.6);
        }

        @media (max-width: 480px) {
            .auth-card { padding: 1.75rem 1.25rem; }
            .auth-wrapper { padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <div class="auth-brand">
        <div class="auth-brand-icon">
            <i class="bx bx-building-house"></i>
        </div>
        <h1 class="auth-brand-name">{{ $appBrandName }}</h1>
        <p class="auth-brand-tagline">Sistem Manajemen Kost Modern</p>
    </div>

    <div class="auth-card">
        <h2 class="auth-card-title">Selamat Datang 👋</h2>
        <p class="auth-card-subtitle">Masuk untuk mengelola kost Anda.</p>

        @if($errors->any())
            <div class="auth-error-box">
                <i class="bx bx-error-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if(session('status'))
            <div class="auth-error-box" style="background:#f0fdf4;border-color:#a7f3d0;color:#065f46;">
                <i class="bx bx-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="nama@email.com"
                    autofocus
                    autocomplete="email"
                    required
                />
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                        style="padding-right: 2.75rem;"
                    />
                    <button type="button" class="password-toggle" id="togglePassword" tabindex="-1">
                        <i class="bx bx-hide" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bx bx-log-in me-1"></i> Masuk
            </button>
        </form>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} {{ $appBrandName }} — Semua hak dilindungi.
    </div>

</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bx bx-show';
        } else {
            input.type = 'password';
            icon.className = 'bx bx-hide';
        }
    });
</script>

</body>
</html>
