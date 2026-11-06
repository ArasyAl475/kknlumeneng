<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@hasSection('judul')@yield('judul') · @endif Admin {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[16rem_minmax(0,1fr)]">

        {{-- SIDEBAR --}}
        <aside class="bg-kopi-tua text-white lg:flex lg:flex-col">
            <div class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3 lg:block lg:py-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-white/50">Panel Admin</p>
                    <a href="{{ route('admin.beranda') }}"
                       class="mt-0.5 inline-flex min-h-11 items-center text-base font-bold leading-tight focus-visible:outline-white">
                        Desa Lumeneng
                    </a>
                </div>

                {{-- Mobile toggle --}}
                <details class="lg:hidden" id="detail-admin-menu">
                    <summary class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 font-bold hover:bg-white/10 focus-visible:outline-white">
                        <x-ikon nama="menu" class="size-5" />Menu
                    </summary>
                    <nav aria-label="Menu admin" class="absolute inset-x-0 z-[1000] border-t border-white/10 bg-kopi-tua px-4 pb-4 shadow-xl">
                        @include('layouts.partials.menu-admin')
                    </nav>
                </details>
            </div>

            <nav aria-label="Menu admin" class="hidden flex-1 overflow-y-auto px-3 py-4 lg:block">
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
