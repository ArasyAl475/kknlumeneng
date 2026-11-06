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
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    @php $menu = \App\Support\Modul::tersedia(); @endphp

    {{-- HEADER NAVBAR --}}
    <header class="sticky top-0 z-[1000] bg-kopi shadow-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-x-4 px-4 py-0">

            {{-- Logo & Nama Desa --}}
            <a href="{{ route('landing') }}" class="flex items-center gap-3 py-3 focus-visible:outline-white">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12 L12 3 L21 12 V21 H15 V15 H9 V21 H3 Z"/>
                    </svg>
                </span>
                <span class="leading-tight">
                    <span class="block text-xs font-normal text-white/70">Portal Resmi</span>
                    <span class="block font-bold text-white">Desa Lumeneng</span>
                </span>
            </a>

            {{-- Nav Desktop --}}
            @if ($menu)
                <nav aria-label="Menu utama" class="hidden items-center gap-1 sm:flex">
                    @foreach ($menu as $modul)
                        <a href="{{ route($modul['rute']) }}"
                           class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-white {{ request()->routeIs($modul['rute']) ? 'bg-white/20 text-white' : '' }}">
                            <x-ikon :nama="$modul['ikon']" class="size-4" />
                            {{ $modul['nama'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Mobile Menu --}}
                <details class="sm:hidden" id="detail-menu">
                    <summary class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 font-bold text-white focus-visible:outline-white">
                        <x-ikon nama="menu" class="size-5" />
                        <span class="text-sm">Menu</span>
                    </summary>
                    <nav aria-label="Menu utama"
                         class="absolute inset-x-0 z-[1000] border-t border-white/10 bg-kopi-tua px-4 py-3 shadow-xl">
                        @foreach ($menu as $modul)
                            <a href="{{ route($modul['rute']) }}"
                               class="flex min-h-12 items-center gap-3 rounded-lg px-3 py-2 font-bold text-white hover:bg-white/10 focus-visible:outline-white">
                                <x-ikon :nama="$modul['ikon']" class="size-5" />
                                {{ $modul['nama'] }}
                            </a>
                        @endforeach
                    </nav>
                </details>
            @endif
        </div>
    </header>

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

    {{-- Close mobile menu on link click --}}
    <script>
        document.querySelectorAll('#detail-menu nav a').forEach(a => {
            a.addEventListener('click', () => {
                document.getElementById('detail-menu').removeAttribute('open');
            });
        });
    </script>
</body>
</html>
