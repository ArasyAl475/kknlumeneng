<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('judul')@yield('judul') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('deskripsi', 'Web terpadu Desa Lumeneng, Kecamatan Paninggaran, Kabupaten Pekalongan: peringatan dini longsor, toko desa, dan edukasi.')">
    <meta name="theme-color" content="#0f2a5c">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Dipasang sebelum halaman tergambar agar efek muncul tidak berkedip. Bila app.js gagal dimuat
         (sinyal lemah), semua bagian dipaksa tampil setelah 2,5 detik, jadi isi tidak pernah tersembunyi. --}}
    <script>document.documentElement.classList.add('js', 'js-siap'); window.__paksaTampil = setTimeout(function () { document.querySelectorAll('[data-muncul],[data-stagger]').forEach(function (e) { e.classList.add('tampak'); }); }, 2500);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="tema-hijau flex min-h-dvh flex-col font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    @include('layouts.partials.navbar-publik')

    {{-- Bagian lebar penuh (foto hero / pita judul) di luar wadah isi. --}}
    @yield('hero')

    <main id="isi" @class(['mx-auto w-full max-w-6xl flex-1 px-4', 'py-8 sm:py-10' => ! View::hasSection('isi-rapat'), 'pb-10' => View::hasSection('isi-rapat')])>
        <x-pesan />
        @yield('isi')
    </main>

    @include('layouts.partials.footer-publik')

    @stack('skrip')
</body>
</html>
