<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0f2a5c">
    <title>@hasSection('judul')@yield('judul') · @endif Admin {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
{{-- tema-admin: latar abu kebiruan yang tenang untuk data padat (lihat app.css). --}}
<body class="tema-admin font-sans">
    <x-ikon-sprite />
    <a href="#isi" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[1100] focus:rounded-lg focus:bg-permukaan focus:px-4 focus:py-3 focus:font-bold focus:shadow-lg">Lompat ke isi</a>

    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[16rem_minmax(0,1fr)]">

        {{-- SIDEBAR. Di HP menempel di atas layar agar tombol Menu selalu terjangkau. --}}
        <aside class="pola-kontur sticky top-0 z-40 bg-navy text-white lg:flex lg:h-dvh lg:flex-col">
            <div class="flex items-center justify-between gap-2 px-4 py-2 lg:px-5 lg:py-5">
                <a href="{{ route('admin.beranda') }}" class="flex min-h-11 items-center gap-3 rounded-xl focus-visible:outline-white">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-biru to-navy-tua ring-1 ring-white/15">
                        <svg class="size-6 text-aksen-terang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 19.5 9 9l4 6 2.5-3.5 6 8Z" /><circle cx="17" cy="6" r="2.2" /></svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block font-judul text-base font-extrabold">Desa Lumeneng</span>
                        <span class="mt-1 inline-block rounded-full bg-white/12 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-langit">Panel Admin</span>
                    </span>
                </a>

                {{-- Menu HP --}}
                <details class="lg:hidden" id="detail-admin-menu">
                    <summary class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white/10 px-4 py-2 font-bold hover:bg-white/15 focus-visible:outline-white">
                        <x-ikon nama="menu" class="size-5" />Menu
                    </summary>
                    <nav aria-label="Menu admin" class="tanpa-batang-gulir absolute inset-x-0 top-full z-[1000] max-h-[80dvh] overflow-y-auto border-t border-white/10 bg-navy-tua px-3 pb-4 shadow-xl">
                        @include('layouts.partials.menu-admin', ['tempatMenu' => 'hp'])
                        <div class="mt-4 border-t border-white/10 pt-3">
                            @include('layouts.partials.bawah-menu-admin')
                        </div>
                    </nav>
                </details>
            </div>

            <nav aria-label="Menu admin" class="tanpa-batang-gulir hidden min-h-0 flex-1 overflow-y-auto px-3 pb-4 lg:block">
                @include('layouts.partials.menu-admin', ['tempatMenu' => 'layar'])
            </nav>
            <div class="hidden shrink-0 border-t border-white/10 px-3 py-3 lg:block">
                @include('layouts.partials.bawah-menu-admin')
            </div>
        </aside>

        {{-- ISI --}}
        <div class="min-w-0">
            <div class="hidden border-b border-garis-halus bg-permukaan/80 backdrop-blur lg:block">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-8 py-3">
                    <p class="flex items-center gap-2 text-sm text-redup">
                        <x-ikon nama="beranda" class="size-4" />
                        <a href="{{ route('admin.beranda') }}" class="hover:text-biru hover:underline">Panel Admin</a>
                        @hasSection('judul')
                            <span aria-hidden="true">/</span>
                            <span class="font-bold text-tinta">@yield('judul')</span>
                        @endif
                    </p>
                    <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-bold text-biru hover:bg-biru-lembut">
                        Lihat situs publik
                        <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>

            <main id="isi" class="px-4 py-6 lg:px-8 lg:py-8">
                <div class="mx-auto max-w-6xl">
                    <x-pesan />
                    @yield('isi')
                </div>
            </main>
        </div>
    </div>

    <x-ke-atas />

    @stack('skrip')
</body>
</html>
