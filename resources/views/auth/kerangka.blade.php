<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0f2a5c">
    <title>@yield('judul') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ site_image('situs.logo') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Kerangka halaman masuk, lupa sandi, dan atur ulang sandi: foto desa penuh (slot Desain Web
     "masuk.latar") dan satu kotak isian padat. --}}
<body class="tema-admin font-sans">
    <x-ikon-sprite />
    <main id="isi" class="relative isolate grid min-h-dvh place-items-center overflow-hidden bg-navy px-4 py-10">
        <x-gambar-situs kunci="masuk.latar" :prioritas="true" class="absolute inset-0 -z-20 size-full" />
        <div class="pola-kontur absolute inset-0 -z-10 bg-gradient-to-br from-navy-tua/85 via-navy/70 to-biru-tua/60" aria-hidden="true"></div>

        <div class="w-full max-w-md motion-safe:animate-muncul">
            <a href="{{ route('landing') }}" class="kaca mx-auto mb-6 flex w-fit items-center gap-3 rounded-2xl px-4 py-2.5 text-white focus-visible:outline-white">
                <x-logo-desa class="size-10" />
                <span class="leading-tight">
                    <span class="block font-judul font-extrabold">Desa Lumeneng</span>
                    <span class="block text-xs text-white/80">Panel pengelola situs</span>
                </span>
            </a>

            <div class="rounded-3xl bg-permukaan p-6 shadow-2xl sm:p-8">
                @yield('isi')
            </div>

            <p class="mt-6 text-center">
                @hasSection('tautan-bawah')
                    @yield('tautan-bawah')
                @else
                    <a href="{{ route('landing') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 font-bold text-white hover:underline focus-visible:outline-white">
                        <x-ikon nama="panah" class="size-4 rotate-180" /> Kembali ke halaman utama
                    </a>
                @endif
            </p>
        </div>
    </main>
</body>
</html>
