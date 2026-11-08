@extends('layouts.app')
@section('judul', $materi->judul)
@section('deskripsi', $materi->ringkasan)

@if ($pratinjau)
    @push('kepala')
        <meta name="robots" content="noindex">
    @endpush
@endif

@push('skrip')
    @if ($materi->video_youtube)
        @vite('resources/js/edukasi/publik.js')
    @endif
@endpush

@section('isi')
    <article class="mx-auto max-w-3xl">
        @if ($pratinjau)
            <div role="status" class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-md border-2 border-kopi bg-permukaan p-3">
                <p class="font-bold">
                    Ini pratinjau.
                    {{ $materi->sudahTerbit() ? 'Materi ini sudah terbit.' : 'Warga belum bisa melihat materi ini.' }}
                </p>
                <x-tombol varian="kedua" :href="route('admin.edukasi.materi.edit', $materi)">Kembali mengubah</x-tombol>
            </div>
        @else
            <a href="{{ route('edukasi.index') }}" class="tautan mb-2 inline-flex min-h-11 items-center">Kembali ke semua materi</a>
        @endif

        <p class="font-bold text-redup">{{ $materi->kategori->nama }}</p>
        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $materi->judul }}</h1>
        <p class="mt-2 text-lg">{{ $materi->ringkasan }}</p>
        <p class="mt-1 text-sm text-redup">Diperbarui {{ $materi->updated_at->format('d-m-Y H:i') }} WIB</p>

        @if ($materi->urlGambar())
            <img src="{{ $materi->urlGambar() }}" alt="{{ $materi->judul }}" decoding="async" class="mt-4 w-full rounded-lg border border-garis-halus">
        @endif

        @if ($materi->video_youtube)
            {{-- Pemutar YouTube baru dimuat saat tombol ditekan (lihat resources/js/edukasi/publik.js).
                 Tanpa JavaScript, tombol berfungsi sebagai tautan biasa ke YouTube. --}}
            <a href="https://www.youtube.com/watch?v={{ $materi->video_youtube }}" data-youtube="{{ $materi->video_youtube }}" data-judul="{{ $materi->judul }}" rel="noopener" class="relative mt-4 flex aspect-video w-full flex-col items-center justify-center gap-2 overflow-hidden rounded-lg border border-garis-halus bg-black p-4 text-center font-bold text-white">
                {{-- Thumbnail ukuran sedang (320x180, sekitar 10 KB) agar hemat kuota. Jika gagal dimuat, latar hitam tetap tampil. --}}
                <img src="https://i.ytimg.com/vi/{{ $materi->video_youtube }}/mqdefault.jpg" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" onerror="this.remove()">
                <span class="relative flex flex-col items-center gap-2 rounded-lg bg-black/60 px-4 py-3">
                    <svg class="size-14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>
                    <span>Putar video</span>
                </span>
            </a>
        @endif

        <div class="mt-5 space-y-4">
            @if (filled($materi->penjelasan))
                <x-kartu judul="Penjelasan"><p class="whitespace-pre-line">{{ $materi->penjelasan }}</p></x-kartu>
            @endif

            @if ($materi->langkah->isNotEmpty())
                <x-kartu judul="Langkah-langkah">
                    <ol class="space-y-5">
                        @foreach ($materi->langkah as $l)
                            <li>
                                <h3 class="font-bold">{{ $loop->iteration }}. {{ $l->judul }}</h3>
                                <p class="mt-1 whitespace-pre-line">{{ $l->isi }}</p>
                                @if ($l->urlGambar())
                                    <img src="{{ $l->urlGambar() }}" alt="Gambar langkah {{ $loop->iteration }}: {{ $l->judul }}" loading="lazy" decoding="async" class="mt-2 w-full rounded-md border border-garis-halus">
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-kartu>
            @endif
        </div>
    </article>
@endsection
