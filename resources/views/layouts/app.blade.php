<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('judul')@yield('judul') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('deskripsi', 'Web terpadu Desa Lumeneng, Kecamatan Paninggaran, Kabupaten Pekalongan: peringatan dini longsor, toko desa, dan edukasi.')">
    <meta name="theme-color" content="#5a3a20">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="flex min-h-dvh flex-col font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded focus:bg-permukaan focus:p-3 focus:font-bold">Lompat ke isi</a>

    @php $menu = \App\Support\Modul::tersedia(); @endphp
    <header class="bg-kopi text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-x-4 px-4 py-1">
            <a href="{{ route('landing') }}" class="inline-flex min-h-11 items-center font-bold focus-visible:outline-white">Desa Lumeneng</a>

            @if ($menu)
                <nav aria-label="Menu utama" class="hidden items-center gap-1 sm:flex">
                    @foreach ($menu as $modul)
                        <a href="{{ route($modul['rute']) }}" class="inline-flex min-h-11 items-center rounded px-3 hover:bg-kopi-tua focus-visible:outline-white">{{ $modul['nama'] }}</a>
                    @endforeach
                </nav>
                <details class="sm:hidden">
                    <summary class="inline-flex min-h-11 items-center gap-2 rounded px-2 font-bold focus-visible:outline-white"><x-ikon nama="menu" />Menu</summary>
                    <nav aria-label="Menu utama" class="absolute inset-x-0 z-[1000] flex flex-col bg-kopi-tua px-4 py-2">
                        @foreach ($menu as $modul)
                            <a href="{{ route($modul['rute']) }}" class="inline-flex min-h-11 items-center font-bold focus-visible:outline-white">{{ $modul['nama'] }}</a>
                        @endforeach
                    </nav>
                </details>
            @endif
        </div>
    </header>

    <main id="isi" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
        <x-pesan />
        @yield('isi')
    </main>

    <footer class="border-t border-garis-halus bg-permukaan">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-1 px-4 py-4 text-sm text-redup">
            <p class="font-bold text-tinta">Informasi peringatan dini di situs ini adalah alat bantu, bukan peringatan resmi BPBD.</p>
            <p>Program KKN / Aksara Peduli · Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan</p>
        </div>
    </footer>
    @stack('skrip')
</body>
</html>
