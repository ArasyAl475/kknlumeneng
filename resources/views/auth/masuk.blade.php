<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0f2a5c">
    <title>Masuk admin · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Halaman masuk berdiri sendiri: foto desa penuh (slot Desain Web "masuk.latar") dan satu kotak isian padat. --}}
<body class="tema-admin font-sans">
    <x-ikon-sprite />
    <main id="isi" class="relative isolate grid min-h-dvh place-items-center overflow-hidden bg-navy px-4 py-10">
        <x-gambar-situs kunci="masuk.latar" :prioritas="true" class="absolute inset-0 -z-20 size-full" />
        <div class="pola-kontur absolute inset-0 -z-10 bg-gradient-to-br from-navy-tua/85 via-navy/70 to-biru-tua/60" aria-hidden="true"></div>

        <div class="w-full max-w-md motion-safe:animate-muncul">
            <a href="{{ route('landing') }}" class="kaca mx-auto mb-6 flex w-fit items-center gap-3 rounded-2xl px-4 py-2.5 text-white focus-visible:outline-white">
                <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-biru to-navy-tua ring-1 ring-white/20">
                    <svg class="size-6 text-aksen-terang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 19.5 9 9l4 6 2.5-3.5 6 8Z" /><circle cx="17" cy="6" r="2.2" /></svg>
                </span>
                <span class="leading-tight">
                    <span class="block font-judul font-extrabold">Desa Lumeneng</span>
                    <span class="block text-xs text-white/80">Panel pengelola situs</span>
                </span>
            </a>

            <div class="rounded-3xl bg-permukaan p-6 shadow-2xl sm:p-8">
                <h1 class="text-2xl font-extrabold text-navy">Masuk admin</h1>
                <p class="mt-1 text-redup">Khusus pengelola desa. Warga tidak perlu masuk untuk melihat status peringatan dini.</p>

                <div class="mt-5">
                    <x-pesan />
                </div>

                <form method="POST" action="{{ route('masuk.proses') }}" class="space-y-4">
                    @csrf
                    <x-bidang nama="email" label="Email pengelola" type="email" autocomplete="username" required autofocus />
                    <x-bidang nama="password" label="Kata sandi" type="password" value="" autocomplete="current-password" required />
                    <x-tombol class="mt-2 w-full py-3 text-base">Masuk ke dasbor admin <x-ikon nama="panah" class="size-5" /></x-tombol>
                </form>
            </div>

            <p class="mt-6 text-center">
                <a href="{{ route('landing') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 font-bold text-white hover:underline focus-visible:outline-white">
                    <x-ikon nama="panah" class="size-4 rotate-180" /> Kembali ke halaman utama
                </a>
            </p>
        </div>
    </main>
</body>
</html>
