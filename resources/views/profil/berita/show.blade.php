@extends('layouts.app')
@section('judul', $berita->judul)
@section('deskripsi', $berita->ringkasan)

@if ($pratinjau)
    @push('kepala')
        <meta name="robots" content="noindex">
    @endpush
@endif

@php
    // Tautan WhatsApp: cara berbagi yang paling akrab bagi warga desa.
    $teksBagikan = $berita->judul.' - '.route('berita.show', $berita->slug);
@endphp

@if ($berita->foto->isNotEmpty())
    @push('skrip')
        @vite('resources/js/profil/galeri.js')
    @endpush
@endif

@section('isi')
    <article class="mx-auto max-w-3xl">
        @if ($pratinjau)
            <div role="status" class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border-2 border-kopi bg-permukaan p-4">
                <p class="font-bold">
                    Ini pratinjau.
                    {{ $berita->sudahTerbit() ? 'Tulisan ini sudah terbit.' : 'Warga belum bisa melihat tulisan ini.' }}
                </p>
                <x-tombol varian="kedua" :href="route('admin.profil.berita.edit', $berita)">Kembali mengubah</x-tombol>
            </div>
        @else
            <a href="{{ route('berita.index') }}" class="mb-3 inline-flex min-h-11 items-center gap-2 rounded-xl px-1 font-bold text-kopi hover:underline">
                <x-ikon nama="panah" class="size-4 rotate-180" /> Semua berita
            </a>
        @endif

        <header>
            @include('profil.partials.jenis')
            <h1 class="mt-3 text-[clamp(1.7rem,5vw,2.6rem)] font-bold leading-tight text-balance">{{ $berita->judul }}</h1>
            <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-redup">
                @if ($berita->diterbitkan_pada)
                    <span class="inline-flex items-center gap-1.5"><x-ikon nama="kalender" class="size-4" /><time datetime="{{ $berita->diterbitkan_pada->toIso8601String() }}">{{ $berita->diterbitkan_pada->format('d-m-Y H:i') }} WIB</time></span>
                @endif
                @if ($berita->penulis)
                    <span class="inline-flex items-center gap-1.5"><x-ikon nama="orang" class="size-4" />{{ $berita->penulis }}</span>
                @endif
                <span class="inline-flex items-center gap-1.5"><x-ikon nama="jam" class="size-4" />{{ $berita->menitBaca() }} menit baca</span>
            </p>
        </header>

        @if ($berita->urlGambar())
            <img src="{{ $berita->urlGambar() }}" alt="{{ $berita->judul }}" decoding="async" class="mt-5 w-full rounded-3xl border border-garis-halus object-cover shadow-sm">
        @endif

        <div class="mt-6 rounded-3xl bg-permukaan p-5 shadow-sm ring-1 ring-garis-halus sm:p-8">
            <p class="border-l-4 border-kopi pl-4 text-lg font-bold leading-relaxed sm:text-xl">{{ $berita->ringkasan }}</p>
            {{-- isiHtml() sudah dibersihkan IsiKaya, jadi aman ditampilkan sebagai HTML. --}}
            <div class="isi-kaya mt-5">{{ $berita->isiHtml() }}</div>
        </div>

        @if ($berita->foto->isNotEmpty())
            <section aria-labelledby="judul-dokumentasi" class="mt-8">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="h-1 w-6 rounded-full bg-kopi" aria-hidden="true"></span>
                        <h2 id="judul-dokumentasi" class="text-xl font-bold sm:text-2xl">Dokumentasi kegiatan</h2>
                    </div>
                    <p class="text-redup">{{ $berita->foto->count() }} foto · ketuk untuk melihat lebih besar</p>
                </div>
                <ul data-galeri class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($berita->foto as $f)
                        {{-- Saat pratinjau, berita belum terbit sehingga halaman galeri masih 404; buka berkas fotonya saja. --}}
                        <li>@include('profil.partials.kotak-foto', ['foto' => $f, 'tampilJudul' => filled($f->judul), 'jembatan' => false, 'tautan' => $pratinjau ? $f->urlGambar() : null])</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @unless ($pratinjau)
            <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl bg-kopi/10 p-4">
                <p class="flex-1 basis-48 font-bold">Bermanfaat? Bagikan ke keluarga dan tetangga.</p>
                <a href="https://wa.me/?text={{ rawurlencode($teksBagikan) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-kopi px-5 font-bold text-white shadow-sm transition hover:bg-kopi-tua">
                    <x-ikon nama="bagikan" class="size-5" /> Bagikan lewat WhatsApp
                </a>
            </div>
        @endunless
    </article>

    @if ($lainnya->isNotEmpty())
        <section aria-labelledby="judul-lainnya" class="mt-12">
            @include('profil.partials.judul-bagian', ['id' => 'judul-lainnya', 'judul' => 'Berita lainnya'])
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($lainnya as $b)
                    <li>@include('profil.partials.kartu-berita', ['berita' => $b])</li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
