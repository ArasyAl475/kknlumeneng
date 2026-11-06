<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#00361f">
    <title>@hasSection('judul')@yield('judul') · @endif Admin {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
{{-- tema-admin: palet hijau panel admin (lihat app.css). Halaman publik lain tetap memakai palet kopi. --}}
<body class="tema-admin font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[17rem_minmax(0,1fr)]">

        {{-- SIDEBAR --}}
        <aside class="relative bg-gradient-to-b from-kopi-tua to-kopi text-white lg:sticky lg:top-0 lg:flex lg:h-dvh lg:flex-col">
            <div class="flex items-center justify-between gap-2 px-4 py-3 lg:px-5 lg:py-6">
                <a href="{{ route('admin.beranda') }}" class="flex min-h-11 items-center gap-3 rounded-xl focus-visible:outline-white">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                        <svg class="size-6 text-[#97f5cc]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 6.5 12H10l-3 5.5h10L14 12h3.5L12 3ZM12 17.5V21" /></svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-base font-bold">Desa Lumeneng</span>
                        <span class="mt-1 inline-block rounded-full bg-[#6cf8bb]/20 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-[#97f5cc]">Panel Admin</span>
                    </span>
                </a>

                {{-- Mobile toggle --}}
                <details class="lg:hidden" id="detail-admin-menu">
                    <summary class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white/10 px-4 py-2 font-bold hover:bg-white/15 focus-visible:outline-white">
                        <x-ikon nama="menu" class="size-5" />Menu
                    </summary>
                    <nav aria-label="Menu admin" class="tanpa-batang-gulir absolute inset-x-0 top-full z-[1000] max-h-[80dvh] overflow-y-auto border-t border-white/10 bg-kopi-tua px-3 pb-4 shadow-xl">
                        @include('layouts.partials.menu-admin')
                    </nav>
                </details>
            </div>

            <nav aria-label="Menu admin" class="tanpa-batang-gulir hidden flex-1 overflow-y-auto px-3 pb-4 lg:block">
                @include('layouts.partials.menu-admin')
            </nav>
        </aside>

        {{-- MAIN --}}
        <main id="isi" class="min-w-0 bg-kertas px-4 py-6 lg:px-8 lg:py-8">
            <div class="mx-auto max-w-5xl">
                <x-pesan />
                @yield('isi')
            </div>
        </main>
    </div>

    @stack('skrip')
</body>
</html>
