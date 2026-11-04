@extends('layouts.app')
@section('judul', __('landing.judul_halaman'))
@section('deskripsi', __('landing.deskripsi_meta'))

@php
    $ews = $modul['ews'] ?? null;
    $ikonStatus = ['hijau' => 'aman', 'kuning' => 'waspada', 'merah' => 'awas'];
    $warna = $status['warna'] ?? 'kosong';
@endphp

@section('isi')
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-10">
        <div>
            <p class="font-bold text-redup">{{ __('landing.hero.program') }}</p>
            <h1 class="mt-2 text-[clamp(1.75rem,6vw,3rem)] leading-tight font-bold text-balance">{{ __('landing.hero.judul') }}</h1>
            <p class="mt-3 max-w-xl text-lg">{{ __('landing.hero.tujuan') }}</p>
            @if ($ews && $ews['tersedia'])
                <x-tombol :href="route($ews['rute'])" class="mt-5 w-full px-6 text-lg sm:w-auto">{{ __('landing.hero.tombol') }}</x-tombol>
            @endif
        </div>

        {{-- Pertanyaan utama warga adalah "aman atau tidak", jadi jawabannya ditempatkan di layar pertama. --}}
        <section data-warna="{{ $warna }}" aria-labelledby="judul-status" class="status-pekat rounded-lg p-5">
            <h2 id="judul-status" class="font-bold">{{ __('landing.status.judul') }}</h2>
            @if ($status)
                <p class="mt-2 flex items-center gap-3 text-4xl font-bold">
                    <x-ikon :nama="$ikonStatus[$warna]" class="size-10 shrink-0" />{{ $status['status'] }}
                </p>
                @if ($status['lokasi'])
                    <p class="mt-2 font-bold">{{ $status['lokasi'] }}</p>
                @endif
                @if ($status['waktu'])
                    <p class="mt-1 text-sm">{{ __('landing.status.data_terakhir', ['waktu' => $status['waktu']]) }}</p>
                @endif
                @if ($status['ada_offline'])
                    <p class="mt-2 text-sm font-bold">{{ __('landing.status.alat_mati') }}</p>
                @endif
            @else
                <p class="mt-2 flex items-center gap-3 text-2xl font-bold">
                    <x-ikon nama="kosong" class="size-8 shrink-0" />{{ __('landing.status.belum_ada') }}
                </p>
            @endif
        </section>
    </div>

    <section aria-labelledby="judul-modul" class="mt-10">
        <h2 id="judul-modul" class="text-xl font-bold">{{ __('landing.modul.judul') }}</h2>
        <ul class="mt-3 divide-y divide-garis-halus border-y border-garis-halus">
            @foreach ($modul as $kunci => $m)
                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                    <x-ikon :nama="$m['ikon']" class="size-6 shrink-0 text-kopi" />
                    <div class="min-w-0 flex-1 basis-56">
                        <h3 class="font-bold">{{ $m['nama'] }}</h3>
                        <p class="text-redup">{{ $m['deskripsi'] }}</p>
                    </div>
                    @if ($m['tersedia'])
                        <x-tombol varian="kedua" :href="route($m['rute'])" aria-label="{{ __('landing.modul.buka') }} {{ $m['nama'] }}">{{ __('landing.modul.buka') }}</x-tombol>
                    @else
                        <span class="text-sm font-bold text-redup">{{ __('landing.modul.segera') }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <div class="mt-10 grid gap-8 md:grid-cols-2">
        <section aria-labelledby="judul-tentang">
            <h2 id="judul-tentang" class="text-xl font-bold">{{ __('landing.tentang.judul') }}</h2>
            @foreach (__('landing.tentang.isi') as $paragraf)
                <p class="mt-2">{{ $paragraf }}</p>
            @endforeach

            <h2 class="mt-6 text-xl font-bold">{{ __('landing.mitra.judul') }}</h2>
            <ul class="mt-2 space-y-2">
                @foreach (__('landing.mitra.daftar') as $mitra)
                    <li>
                        <span class="font-bold">{{ $mitra['nama'] }}</span>
                        @if ($mitra['peran'])
                            <span class="block text-redup">{{ $mitra['peran'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="judul-kontak">
            <x-kartu>
                <h2 id="judul-kontak" class="text-xl font-bold">{{ __('landing.kontak.judul') }}</h2>
                <p class="mt-3 flex items-start gap-2"><x-ikon nama="lokasi" class="mt-0.5 size-5 shrink-0" />{{ __('landing.kontak.lokasi') }}</p>
                <p class="mt-4 flex items-center gap-2 font-bold"><x-ikon nama="telepon" />{{ __('landing.kontak.darurat_judul') }}</p>
                @if (__('landing.kontak.darurat_nomor'))
                    <a class="tautan mt-1 inline-flex min-h-11 items-center text-2xl" href="tel:{{ __('landing.kontak.darurat_nomor') }}">{{ __('landing.kontak.darurat_nomor') }}</a>
                @else
                    <p class="mt-1">{{ __('landing.kontak.darurat_kosong') }}</p>
                @endif
            </x-kartu>
        </section>
    </div>
@endsection
