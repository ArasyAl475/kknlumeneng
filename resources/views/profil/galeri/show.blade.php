@extends('layouts.app')
@section('judul', $foto->judulTampil().' · Galeri')
@section('deskripsi', $foto->keteranganTampil() ?? 'Foto dari Galeri Desa Lumeneng.')

@php
    $berita = $foto->berita;
    $tanggal = $foto->tanggalTampil();
@endphp

@push('skrip')
    @vite('resources/js/profil/galeri.js')
@endpush

@section('isi')
    <a href="{{ route('galeri.index') }}" class="mb-3 inline-flex min-h-11 items-center gap-2 rounded-xl px-1 font-bold text-kopi hover:underline">
        <x-ikon nama="panah" class="size-4 rotate-180" /> Semua foto
    </a>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start">
        <figure class="overflow-hidden rounded-3xl bg-kopi-tua shadow-lg">
            {{-- object-contain: foto tegak maupun mendatar tampil utuh tanpa terpotong. --}}
            <img src="{{ $foto->urlGambar() }}" alt="{{ $foto->judulTampil() }}" decoding="async" fetchpriority="high" class="mx-auto max-h-[75vh] w-full object-contain">
            <figcaption class="sr-only">{{ $foto->judulTampil() }}</figcaption>
        </figure>

        <div class="space-y-4">
            <div class="rounded-3xl bg-permukaan p-5 shadow-sm ring-1 ring-garis-halus sm:p-6">
                <p class="text-sm font-bold uppercase tracking-wider text-kopi">{{ $foto->terhubung() ? 'Dokumentasi kegiatan' : 'Foto desa' }}</p>
                <h1 class="mt-1 text-2xl font-bold leading-snug text-balance sm:text-3xl">{{ $foto->judulTampil() }}</h1>
                @if ($tanggal)
                    <p class="mt-2 flex items-center gap-1.5 text-redup"><x-ikon nama="kalender" class="size-4" /><time datetime="{{ $tanggal->toDateString() }}">{{ $tanggal->format('d-m-Y') }}</time></p>
                @endif
                @if ($foto->keteranganTampil())
                    <p class="mt-3 whitespace-pre-line text-lg leading-relaxed">{{ $foto->keteranganTampil() }}</p>
                @endif
                <a href="{{ $foto->urlGambar() }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-11 items-center gap-2 font-bold text-kopi underline">
                    <x-ikon nama="perbesar" class="size-4" /> Lihat ukuran penuh
                </a>
            </div>

            {{-- Jembatan ke berita: hanya muncul bila foto terhubung ke kegiatan. --}}
            @if ($berita)
                <a href="{{ route('berita.show', $berita->slug) }}" class="group flex gap-4 rounded-3xl bg-kopi p-4 text-white shadow-md transition hover:bg-kopi-tua focus-visible:outline-offset-4 sm:p-5">
                    @if ($berita->urlGambar())
                        <img src="{{ $berita->urlGambar() }}" alt="" loading="lazy" class="size-20 shrink-0 rounded-xl object-cover sm:size-24">
                    @else
                        <span class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-white/15 sm:size-24" aria-hidden="true"><x-ikon nama="berita" class="size-8" /></span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-bold uppercase tracking-wider text-aksen-terang">Foto ini dari {{ strtolower($berita->labelJenis()) }}</span>
                        <span class="mt-1 block font-bold leading-snug">{{ $berita->judul }}</span>
                        <span class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold">Baca ceritanya <x-ikon nama="panah" class="size-4 motion-safe:transition-transform group-hover:translate-x-1" /></span>
                    </span>
                </a>
            @endif
        </div>
    </div>

    @if ($lainnya->isNotEmpty())
        <section aria-labelledby="judul-lainnya" class="mt-12">
            @include('profil.partials.judul-bagian', ['id' => 'judul-lainnya', 'judul' => $berita ? 'Foto lain dari kegiatan ini' : 'Foto lainnya'])
            <ul data-galeri class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
                @foreach ($lainnya as $f)
                    <li>@include('profil.partials.kotak-foto', ['foto' => $f, 'tampilJudul' => false])</li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
