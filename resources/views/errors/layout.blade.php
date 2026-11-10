<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') · @yield('judul')</title>
    {{-- Gaya sengaja menempel di sini: halaman galat harus tetap tampil walau aset build bermasalah. --}}
    <style>
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 1.5rem; box-sizing: border-box; background: #f8f4ec; color: #0f172a; font: 17px/1.5 system-ui, sans-serif; }
        main { max-width: 32rem; }
        p.kode { margin: 0; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #9a3412; }
        h1 { margin: .25rem 0 .5rem; font-size: 1.9rem; line-height: 1.2; color: #0f2a5c; }
        a { display: inline-flex; align-items: center; min-height: 44px; margin-top: .75rem; padding: 0 1rem; border-radius: 10px; background: #1e40af; color: #fff; font-weight: 700; text-decoration: none; }
        a:focus-visible { outline: 3px solid #0f2a5c; outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <p class="kode">Galat @yield('kode')</p>
        <h1>@yield('judul')</h1>
        <p>@yield('pesan')</p>
        <a href="{{ url('/') }}">Kembali ke beranda</a>
    </main>
</body>
</html>
