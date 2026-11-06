<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') · @yield('judul')</title>
    {{-- Gaya sengaja menempel di sini: halaman galat harus tetap tampil walau aset build bermasalah. --}}
    <style>
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 1.5rem; box-sizing: border-box; background: #f6f2ea; color: #1e2421; font: 17px/1.5 system-ui, sans-serif; }
        main { max-width: 32rem; }
        p.kode { margin: 0; font-weight: 700; color: #56605a; }
        h1 { margin: .25rem 0 .5rem; font-size: 1.75rem; line-height: 1.2; }
        a { display: inline-flex; align-items: center; min-height: 44px; margin-top: .75rem; padding: 0 1rem; border-radius: 6px; background: #164e32; color: #fff; font-weight: 700; text-decoration: none; }
        a:focus-visible { outline: 3px solid #1e2421; outline-offset: 2px; }
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
