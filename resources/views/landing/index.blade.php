@extends('layouts.app')
@section('judul', __('landing.judul_halaman'))
@section('deskripsi', __('landing.deskripsi_meta'))

@php
    $ews = $modul['ews'] ?? null;
    $ikonStatus = ['hijau' => 'aman', 'kuning' => 'waspada', 'merah' => 'awas'];
    $warna = $status['warna'] ?? 'kosong';

    $warnaRing = [
        'hijau'  => 'ring-aman',
        'kuning' => 'ring-waspada',
        'merah'  => 'ring-awas',
        'kosong' => 'ring-garis',
    ];
    $warnaGlow = [
        'hijau'  => 'shadow-[0_0_30px_rgba(15,107,69,0.3)]',
        'kuning' => 'shadow-[0_0_30px_rgba(242,183,5,0.3)]',
        'merah'  => 'shadow-[0_0_30px_rgba(166,27,20,0.4)]',
        'kosong' => '',
    ];
@endphp

@section('isi')

    {{-- ══════════════════════════════════════════
         HERO SECTION
    ══════════════════════════════════════════ --}}
    <section class="mb-10">
        <div class="grid items-stretch gap-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-6">

            {{-- Teks hero --}}
            <div class="flex flex-col justify-center rounded-2xl bg-kopi-tua p-6 text-white sm:p-8">
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider">
                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                    {{ __('landing.hero.program') }}
                </span>

                <h1 class="mt-4 text-[clamp(1.6rem,5.5vw,2.6rem)] leading-tight font-bold text-balance">
                    {{ __('landing.hero.judul') }}
                </h1>

                <p class="mt-3 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">
                    {{ __('landing.hero.tujuan') }}
                </p>

                @if ($ews && $ews['tersedia'])
                    <div class="mt-6">
                        <a href="{{ route($ews['rute']) }}"
                           class="inline-flex min-h-14 items-center gap-3 rounded-xl bg-white px-6 text-base font-bold text-kopi-tua shadow-lg transition-all hover:bg-kertas hover:shadow-xl active:scale-95">
                            <x-ikon nama="aman" class="size-5 text-kopi" />
                            {{ __('landing.hero.tombol') }}
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- STATUS CARD --}}
            <section data-warna="{{ $warna }}"
                     aria-labelledby="judul-status"
                     class="status-pekat {{ $warnaGlow[$warna] }} rounded-2xl p-5 ring-4 ring-inset ring-white/20 sm:p-6">

                <div class="flex items-center justify-between">
                    <h2 id="judul-status" class="text-sm font-bold uppercase tracking-wider opacity-80">
                        {{ __('landing.status.judul') }}
                    </h2>
                    <span class="inline-flex size-3 rounded-full bg-white/60 ring-2 ring-white/30"
                          style="animation: pulse 2s cubic-bezier(0.4,0,0.6,1) infinite;"
                          aria-hidden="true"></span>
                </div>

                @if ($status)
                    <p class="mt-4 flex items-center gap-3 text-[clamp(2.5rem,10vw,3.5rem)] font-bold leading-none">
                        <x-ikon :nama="$ikonStatus[$warna]" class="size-[1em] shrink-0" />
                        <span>{{ $status['status'] }}</span>
                    </p>

                    @if ($status['lokasi'])
                        <p class="mt-3 text-base font-bold opacity-90">
                            <svg class="mr-1 inline size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s7.5-7 7.5-12.5a7.5 7.5 0 0 0-15 0C4.5 15 12 22 12 22Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                            {{ $status['lokasi'] }}
                        </p>
                    @endif

                    @if ($status['waktu'])
                        <p class="mt-2 text-sm opacity-70">
                            {{ __('landing.status.data_terakhir', ['waktu' => $status['waktu']]) }}
                        </p>
                    @endif

                    @if ($status['ada_offline'])
                        <p class="mt-3 rounded-lg bg-black/20 px-3 py-2 text-sm font-bold">
                            ⚠ {{ __('landing.status.alat_mati') }}
                        </p>
                    @endif
                @else
                    <div class="mt-4">
                        <p class="flex items-center gap-3 text-2xl font-bold">
                            <x-ikon nama="kosong" class="size-8 shrink-0" />
                            {{ __('landing.status.belum_ada') }}
                        </p>
                        <p class="mt-2 text-sm opacity-70">Sensor belum mengirim data. Pastikan alat sudah terpasang.</p>
                    </div>
                @endif
            </section>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         MODUL / FITUR
    ══════════════════════════════════════════ --}}
    <section aria-labelledby="judul-modul" class="mb-10">
        <div class="mb-4 flex items-center gap-3">
            <div class="h-1 w-6 rounded-full bg-kopi"></div>
            <h2 id="judul-modul" class="text-xl font-bold sm:text-2xl">{{ __('landing.modul.judul') }}</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($modul as $kunci => $m)
                <article class="group flex flex-col rounded-2xl border-2 border-garis-halus bg-permukaan p-5 transition-all hover:border-kopi hover:shadow-md sm:p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-kopi/10 text-kopi transition-colors group-hover:bg-kopi group-hover:text-white">
                            <x-ikon :nama="$m['ikon']" class="size-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold leading-snug">{{ $m['nama'] }}</h3>
                            <p class="mt-1 text-sm text-redup">{{ $m['deskripsi'] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-garis-halus">
                        @if ($m['tersedia'])
                            <a href="{{ route($m['rute']) }}"
                               class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-kopi px-4 font-bold text-white transition-all hover:bg-kopi-tua active:scale-95"
                               aria-label="{{ isset($m['tombol']) ? $m['nama'] : __('landing.modul.buka').' '.$m['nama'] }}">
                                {{ $m['tombol'] ?? __('landing.modul.buka') }}
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <span class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border-2 border-dashed border-garis-halus px-4 text-sm font-bold text-redup">
                                🕐 {{ __('landing.modul.segera') }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         INFO BAWAH: TENTANG + KONTAK
    ══════════════════════════════════════════ --}}
    <div class="grid gap-6 md:grid-cols-2">

        {{-- TENTANG PROGRAM --}}
        <section aria-labelledby="judul-tentang" class="rounded-2xl border-2 border-garis-halus bg-permukaan p-5 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-1 w-5 rounded-full bg-kopi"></div>
                <h2 id="judul-tentang" class="text-lg font-bold">{{ __('landing.tentang.judul') }}</h2>
            </div>

            @foreach (__('landing.tentang.isi') as $paragraf)
                <p class="mt-2 text-sm leading-relaxed text-redup first:mt-0">{{ $paragraf }}</p>
            @endforeach

            <div class="mt-6 border-t border-garis-halus pt-4">
                <h3 class="mb-3 font-bold">{{ __('landing.mitra.judul') }}</h3>
                <ul class="space-y-3">
                    @foreach (__('landing.mitra.daftar') as $mitra)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-kopi/10 text-xs font-bold text-kopi">
                                {{ mb_substr($mitra['nama'], 0, 2) }}
                            </span>
                            <div>
                                <p class="font-bold leading-snug">{{ $mitra['nama'] }}</p>
                                @if ($mitra['peran'])
                                    <p class="text-sm text-redup">{{ $mitra['peran'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- KONTAK & DARURAT --}}
        <section aria-labelledby="judul-kontak" class="rounded-2xl border-2 border-awas/20 bg-awas-lembut p-5 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="flex size-10 items-center justify-center rounded-full bg-awas/10 text-awas">
                    <x-ikon nama="telepon" class="size-5" />
                </span>
                <h2 id="judul-kontak" class="text-lg font-bold">{{ __('landing.kontak.judul') }}</h2>
            </div>

            <div class="flex items-start gap-2 rounded-xl bg-white/60 p-3 text-sm">
                <x-ikon nama="lokasi" class="mt-0.5 size-5 shrink-0 text-kopi" />
                <span>{{ __('landing.kontak.lokasi') }}</span>
            </div>

            <div class="mt-4">
                <p class="text-sm font-bold text-awas">{{ __('landing.kontak.darurat_judul') }}</p>
                @if (__('landing.kontak.darurat_nomor'))
                    <a href="tel:{{ __('landing.kontak.darurat_nomor') }}"
                       class="mt-2 flex min-h-14 items-center justify-center gap-3 rounded-xl bg-awas px-4 text-2xl font-bold text-white shadow-md transition-all hover:bg-awas-tua active:scale-95">
                        <x-ikon nama="telepon" class="size-6" />
                        {{ __('landing.kontak.darurat_nomor') }}
                    </a>
                @else
                    <div class="mt-2 rounded-xl bg-white/60 p-4">
                        <p class="text-sm text-redup">{{ __('landing.kontak.darurat_kosong') }}</p>
                    </div>
                @endif
            </div>

            {{-- Tips Keselamatan --}}
            <div class="mt-4 rounded-xl bg-kopi-tua/5 p-4 border border-kopi/20">
                <p class="text-xs font-bold uppercase tracking-wide text-kopi">Tips Keselamatan</p>
                <ul class="mt-2 space-y-1.5 text-sm text-redup">
                    <li class="flex gap-2"><span class="shrink-0">🏃</span> Segera menjauh dari lereng saat hujan deras</li>
                    <li class="flex gap-2"><span class="shrink-0">📱</span> Simpan nomor kader Karang Taruna di HP</li>
                    <li class="flex gap-2"><span class="shrink-0">👁️</span> Pantau status ini setiap pagi dan malam</li>
                </ul>
            </div>
        </section>

    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>

@endsection
