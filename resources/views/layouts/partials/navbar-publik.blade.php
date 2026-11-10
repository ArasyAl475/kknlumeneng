{{-- Bilah atas + header publik. Dipakai layouts/app dan toko/kerangka agar menunya selalu sama.
     Di semua halaman header mula-mula transparan, lalu menjadi putih padat setelah digulir (resources/js/app.js).
     Halaman berfoto di bagian atas (@section('hero'): sampul Beranda/Profil atau pita judul) membuat header
     melayang di atas foto dengan teks putih. Halaman tanpa foto (detail berita, materi, produk) memakai teks gelap. --}}
@php
    // "Beranda" ditulis eksplisit: banyak warga tidak tahu bahwa logo bisa diketuk untuk kembali.
    $menu = ['beranda' => ['nama' => 'Beranda', 'rute' => 'landing', 'ikon' => 'beranda']] + \App\Support\Modul::tersedia();
    $aktif = fn (array $modul) => request()->routeIs($modul['pola'] ?? $modul['rute']);
    // Semua bagian hero berlatar gelap (foto + lapisan navy), jadi teks putih di atasnya tetap terbaca.
    $transparan = $__env->hasSection('header-transparan') || $__env->hasSection('hero');
@endphp

{{-- Bilah navy tipis ala portal pemerintah; di HP kecil disembunyikan agar layar tidak sesak. --}}
<div class="hidden bg-navy-tua text-sm text-white/85 sm:block">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-1.5">
        <p class="truncate">Selamat datang di portal <span class="font-bold text-white">Desa Lumeneng</span>, Kec. Paninggaran, Kab. Pekalongan</p>
        <ul class="flex shrink-0 items-center gap-1">
            @if (Route::has('profil.index'))
                <li><a href="{{ route('profil.index') }}#judul-kontak" class="inline-flex min-h-8 items-center gap-1.5 rounded px-2 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="telepon" class="size-4" />Kontak</a></li>
            @endif
            <li>
                @auth
                    <a href="{{ route('admin.beranda') }}" class="inline-flex min-h-8 items-center gap-1.5 rounded px-2 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="orang" class="size-4" />Panel Admin</a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center gap-1.5 rounded px-2 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="orang" class="size-4" />Masuk Admin</a>
                @endauth
            </li>
        </ul>
    </div>
</div>

<header data-navbar-publik data-header-transparan
    @class([
        'group/header sticky top-0 z-[1000] h-[4.25rem] transition-[background-color,box-shadow,color,border-color] duration-200 data-[padat]:bg-white data-[padat]:text-tinta data-[padat]:shadow-[0_6px_24px_-12px_rgb(15_42_92/0.35)]',
        // Margin negatif: foto hero naik ke bawah header, sehingga header tampak melayang di atas foto.
        '-mb-[4.25rem] text-white' => $transparan,
        // Tanpa foto: latar halaman tetap terlihat di balik header; garis bawah baru muncul saat padat.
        'border-b border-transparent text-tinta data-[padat]:border-garis-halus' => ! $transparan,
    ])>
    @if ($transparan)
        {{-- Gradasi tipis agar menu putih tetap terbaca di atas foto yang bagian atasnya terang. --}}
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-navy-tua/70 to-transparent transition-opacity group-data-[padat]/header:opacity-0" aria-hidden="true"></div>
    @endif

    <div class="mx-auto flex h-full max-w-6xl items-center justify-between gap-x-4 px-4">
        <a href="{{ route('landing') }}" class="flex shrink-0 items-center gap-3 rounded-xl py-2">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-biru to-navy text-white shadow-md ring-1 ring-white/20">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2.5 19.5 9 9l4 6 2.5-3.5 6 8Z" /><circle cx="17" cy="6" r="2.2" />
                </svg>
            </span>
            <span class="leading-tight">
                <span class="block font-judul text-[1.05rem] font-extrabold">Desa Lumeneng</span>
                <span @class(['block text-xs', 'text-white/80 group-data-[padat]/header:text-redup' => $transparan, 'text-redup' => ! $transparan])>Kab. Pekalongan · Jawa Tengah</span>
            </span>
        </a>

        {{-- Menu layar lebar (xl ke atas): delapan menu tetap muat dalam satu baris. --}}
        <nav aria-label="Menu utama" class="hidden items-center gap-0.5 xl:flex">
            @foreach ($menu as $modul)
                <a href="{{ route($modul['rute']) }}" @if ($aktif($modul)) aria-current="page" @endif
                   @class([
                       'tautan-menu',
                       'hover:bg-white/15 group-data-[padat]/header:hover:bg-biru-lembut group-data-[padat]/header:hover:text-biru focus-visible:outline-white group-data-[padat]/header:focus-visible:outline-biru' => $transparan,
                       'hover:bg-biru-lembut hover:text-biru' => ! $transparan,
                       'text-aksen-terang group-data-[padat]/header:text-biru' => $transparan && $aktif($modul),
                       'text-biru' => ! $transparan && $aktif($modul),
                   ])>{{ $modul['nama'] }}</a>
            @endforeach
        </nav>

        {{-- Menu HP dan tablet --}}
        <details class="xl:hidden" data-menu-hp>
            <summary @class([
                'inline-flex min-h-11 items-center gap-2 rounded-xl px-4 py-2 font-judul font-bold',
                'kaca-terang group-data-[padat]/header:border-garis-halus group-data-[padat]/header:bg-biru-lembut group-data-[padat]/header:text-biru focus-visible:outline-white' => $transparan,
                'bg-biru-lembut text-biru hover:bg-biru-muda' => ! $transparan,
            ])>
                <x-ikon nama="menu" class="size-5" />
                <span class="text-sm">Menu</span>
            </summary>
            <nav aria-label="Menu utama"
                 class="absolute inset-x-0 top-full z-[1000] grid max-h-[80dvh] gap-1 overflow-y-auto border-t border-garis-halus bg-white px-4 py-3 text-tinta shadow-xl sm:grid-cols-2">
                @foreach ($menu as $modul)
                    <a href="{{ route($modul['rute']) }}" @if ($aktif($modul)) aria-current="page" @endif
                       @class([
                           'flex min-h-12 items-center gap-3 rounded-xl px-3 py-2 font-judul font-bold hover:bg-biru-lembut',
                           'bg-biru-lembut text-biru shadow-[inset_3px_0_0_var(--color-aksen)]' => $aktif($modul),
                       ])>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-biru-lembut text-biru">
                            <x-ikon :nama="$modul['ikon']" class="size-5" />
                        </span>
                        {{ $modul['nama'] }}
                    </a>
                @endforeach
                <a href="{{ auth()->check() ? route('admin.beranda') : route('login') }}" class="flex min-h-12 items-center gap-3 rounded-xl px-3 py-2 font-judul font-bold text-redup hover:bg-biru-lembut sm:hidden">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-krem-tua"><x-ikon nama="orang" class="size-5" /></span>
                    {{ auth()->check() ? 'Panel Admin' : 'Masuk Admin' }}
                </a>
            </nav>
        </details>
    </div>
</header>
