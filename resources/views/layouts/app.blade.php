<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('judul')@yield('judul') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('deskripsi', 'Web terpadu Desa Lumeneng, Kecamatan Paninggaran, Kabupaten Pekalongan: peringatan dini longsor, toko desa, dan edukasi.')">
    <meta name="theme-color" content="#164e32">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="tema-hijau flex min-h-dvh flex-col font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    @include('layouts.partials.navbar-publik')

    {{-- MAIN CONTENT --}}
    <main id="isi" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:py-8">
        <x-pesan />
        @yield('isi')
    </main>

    {{-- FOOTER --}}
    <footer class="mt-8 border-t-2 border-garis-halus bg-kopi-tua text-white">
        <div class="mx-auto max-w-6xl px-4 py-6">
            <div class="flex flex-wrap items-start justify-between gap-6">
                {{-- Brand --}}
                <div class="min-w-[200px]">
                    <p class="text-lg font-bold">Desa Lumeneng</p>
                    <p class="mt-1 text-sm text-white/70">Kec. Paninggaran, Kab. Pekalongan</p>
                    <p class="mt-1 text-sm text-white/70">Jawa Tengah</p>
                </div>
                {{-- Disclaimer --}}
                <div class="max-w-md text-sm text-white/80">
                    <p class="font-bold text-white">⚠ Penting</p>
                    <p class="mt-1">Informasi peringatan dini di situs ini adalah <strong>alat bantu</strong>, bukan peringatan resmi BPBD. Saat kondisi berbahaya, segera menjauh dan hubungi perangkat desa.</p>
                </div>
            </div>
            <div class="mt-5 border-t border-white/15 pt-4 text-center text-xs text-white/50">
                Program KKN / Aksara Peduli Universitas Ivet · Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan
            </div>
        </div>
    </footer>

    @stack('skrip')
</body>
</html>
