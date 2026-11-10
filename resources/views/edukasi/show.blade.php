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
            <a href="{{ route('edukasi.index', ['kategori' => $materi->kategori->slug]) }}" class="tautan mb-2 inline-flex min-h-11 items-center">Kembali ke daftar bagian</a>
        @endif

        <p class="font-bold text-redup">{{ $materi->kategori->nama }}@if ($bagian['posisi']) · Bagian {{ $bagian['posisi'] }} dari {{ $bagian['total'] }}@endif</p>
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

        {{-- Navigasi antarbagian dalam satu kategori. Tidak menyimpan progres belajar: angkanya hanya letak bagian ini. --}}
        @if ($bagian['posisi'])
            @php
                $persen = (int) round($bagian['posisi'] / $bagian['total'] * 100);
                // Satu gaya dasar untuk semua kotak agar tinggi dan lebarnya selalu sama, apa pun panjang judulnya.
                $gayaKotak = 'flex h-full min-h-24 flex-col justify-center gap-1 rounded-xl px-4 py-3 font-bold leading-snug';
            @endphp
            {{-- Tiga kolom sama lebar. Di HP progres di atas, lalu dua tombol berdampingan. --}}
            <nav aria-label="Navigasi bagian" class="mt-8 grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3">
                <div class="order-first col-span-2 flex min-h-24 flex-col items-center justify-center text-center sm:order-2 sm:col-span-1">
                    <p class="text-sm text-redup">Bagian</p>
                    <p class="text-2xl font-bold leading-tight text-kopi">{{ $bagian['posisi'] }}/{{ $bagian['total'] }}</p>
                    <div role="progressbar" aria-label="Letak bagian ini di kategori {{ $materi->kategori->nama }}" aria-valuemin="0" aria-valuemax="{{ $bagian['total'] }}" aria-valuenow="{{ $bagian['posisi'] }}" class="mt-2 h-2 w-full max-w-48 overflow-hidden rounded-full bg-garis-halus">
                        <div class="h-full rounded-full bg-kopi" style="width: {{ $persen }}%"></div>
                    </div>
                </div>

                @if ($bagian['sebelumnya'])
                    <a href="{{ route('edukasi.show', $bagian['sebelumnya']->slug) }}" rel="prev" class="{{ $gayaKotak }} border border-garis-halus bg-permukaan text-left hover:border-kopi sm:order-1">
                        <span class="text-xs font-bold text-redup">← Bagian sebelumnya</span>
                        <span class="line-clamp-2">{{ $bagian['sebelumnya']->judul }}</span>
                    </a>
                @else
                    <span aria-disabled="true" class="{{ $gayaKotak }} bg-kosong-lembut text-left text-redup sm:order-1">
                        <span class="text-xs font-bold">← Bagian sebelumnya</span>
                        <span class="line-clamp-2">Ini bagian pertama</span>
                    </span>
                @endif

                @if ($bagian['berikutnya'])
                    <a href="{{ route('edukasi.show', $bagian['berikutnya']->slug) }}" rel="next" class="{{ $gayaKotak }} bg-kopi text-right text-white hover:bg-kopi-tua sm:order-3">
                        <span class="text-xs font-bold text-white/80">Bagian selanjutnya →</span>
                        <span class="line-clamp-2">{{ $bagian['berikutnya']->judul }}</span>
                    </a>
                @else
                    <a href="{{ route('edukasi.index', ['kategori' => $materi->kategori->slug]) }}" class="{{ $gayaKotak }} bg-kopi text-right text-white hover:bg-kopi-tua sm:order-3">
                        <span class="text-xs font-bold text-white/80">Bagian terakhir</span>
                        <span class="line-clamp-2">Kembali ke daftar</span>
                    </a>
                @endif
            </nav>

            <x-kartu :judul="'Semua bagian: '.$materi->kategori->nama" class="mt-5">
                <ol class="divide-y divide-garis-halus">
                    @foreach ($bagian['daftar'] as $b)
                        @php $sedangDibaca = $b->id === $materi->id; @endphp
                        <li>
                            <a href="{{ route('edukasi.show', $b->slug) }}" @class(['flex min-h-11 items-center gap-3 py-2 hover:underline', 'font-bold text-kopi' => $sedangDibaca]) @if ($sedangDibaca) aria-current="page" @endif>
                                <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold', 'bg-kopi text-white' => $sedangDibaca, 'bg-kosong-lembut text-redup' => ! $sedangDibaca])>{{ $loop->iteration }}</span>
                                <span class="min-w-0 flex-1">{{ $b->judul }}</span>
                                @if ($sedangDibaca)
                                    <span class="shrink-0 text-xs">Sedang dibaca</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            </x-kartu>
        @endif
    </article>
@endsection
