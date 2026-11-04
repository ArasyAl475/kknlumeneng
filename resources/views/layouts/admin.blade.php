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
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[1100] focus:rounded focus:bg-permukaan focus:p-3 focus:font-bold">Lompat ke isi</a>

    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="bg-kopi-tua text-white">
            <div class="flex items-center justify-between gap-2 px-4 py-1 lg:block lg:py-3">
                <a href="{{ route('admin.beranda') }}" class="inline-flex min-h-11 items-center font-bold focus-visible:outline-white">Admin Desa Lumeneng</a>
                <details class="lg:hidden">
                    <summary class="inline-flex min-h-11 items-center gap-2 font-bold focus-visible:outline-white"><x-ikon nama="menu" />Menu</summary>
                    <nav aria-label="Menu admin" class="absolute inset-x-0 z-[1000] bg-kopi-tua px-4 pb-3">
                        @include('layouts.partials.menu-admin')
                    </nav>
                </details>
            </div>
            <nav aria-label="Menu admin" class="hidden px-4 pb-4 lg:block">
                @include('layouts.partials.menu-admin')
            </nav>
        </aside>

        <main id="isi" class="min-w-0 px-4 py-6 lg:px-8">
            <div class="mx-auto max-w-5xl">
                <x-pesan />
                @yield('isi')
            </div>
        </main>
    </div>
    @stack('skrip')
</body>
</html>
