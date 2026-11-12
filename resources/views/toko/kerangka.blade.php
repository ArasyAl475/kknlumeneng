{{-- Kerangka halaman Hasil Panen. Header, pita judul, dan footer memakai bagian bersama agar sama dengan halaman publik lain;
     isinya tetap memakai gaya khusus resources/css/toko-hasil.css. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('judul') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('deskripsi')">
    <meta name="theme-color" content="#0f2a5c">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>document.documentElement.classList.add('js');</script>
    {{-- Huruf diunduh lebih awal agar teks tidak "melompat" saat huruf cadangan berganti huruf asli. --}}
    <link rel="preload" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/plus-jakarta-sans-latin-wght.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/css/toko-hasil.css', 'resources/js/app.js', 'resources/js/toko/hasil.js'])
    @stack('kepala')
</head>
<body class="hasil tema-hijau">
    <x-ikon-sprite />
    <a href="#isi" class="lompat">Lompat ke isi</a>

    {{-- Navbar sama dengan halaman lain (Toko, Edukasi, Peringatan Dini) supaya warga tidak "terkunci" di halaman ini. --}}
    @include('layouts.partials.navbar-publik')

    {{-- Pita judul berfoto (slot Desain Web), lebar penuh di luar wadah isi. --}}
    @yield('hero')

    <main id="isi" class="hasil-lebar hasil-isi">
        @yield('isi')
    </main>

    @include('layouts.partials.footer-publik')

    @stack('skrip')
</body>
</html>
