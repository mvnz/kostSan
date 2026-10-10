<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses dibatasi</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 1.5rem; font: 1rem/1.6 system-ui, sans-serif; background: #f5f7fb; color: #24334b; }
        main { max-width: 36rem; margin: 10vh auto; padding: 1.5rem; background: white; border-radius: .75rem; }
        a, button { display: inline-block; padding: .6rem .9rem; margin: .25rem .25rem .25rem 0; border: 1px solid #2458a4; border-radius: .4rem; background: white; color: #2458a4; font: inherit; cursor: pointer; }
        form { display: inline; }
    </style>
</head>
<body>
<main>
    <h1>Akses dibatasi</h1>
    <p>Anda tidak memiliki izin untuk membuka halaman ini. Hubungi pengelola untuk memeriksa hak akses akun Anda.</p>
    @auth
        <a href="{{ route('profile.show') }}">Profil akun</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Keluar</button>
        </form>
    @else
        <a href="{{ route('login') }}">Masuk</a>
    @endauth
</main>
</body>
</html>
