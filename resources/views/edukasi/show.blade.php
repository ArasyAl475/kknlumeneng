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
            <div role="status" class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border-2 border-biru bg-biru-lembut p-4">
                <p class="font-bold">
                    Ini pratinjau.
                    {{ $materi->sudahTerbit() ? 'Materi ini sudah terbit.' : 'Warga belum bisa melihat materi ini.' }}
                </p>
                <x-tombol varian="kedua" :href="route('admin.edukasi.materi.edit', $materi)">Kembali mengubah</x-tombol>
            </div>
        @else
            <nav aria-label="Jejak halaman" class="mb-4 text-sm text-redup">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('landing') }}" class="hover:text-biru hover:underline">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('edukasi.index', ['kategori' => $materi->kategori->slug]) }}" class="inline-flex min-h-11 items-center font-bold text-biru hover:underline">Kembali ke daftar bagian</a></li>
                </ol>
            </nav>
        @endif

        <p class="kicker">{{ $materi->kategori->nama }}@if ($bagian['posisi']) · Bagian {{ $bagian['posisi'] }} dari {{ $bagian['total'] }}@endif</p>
        <h1 class="mt-2 text-[clamp(1.8rem,5vw,2.6rem)] font-extrabold leading-tight text-navy text-balance">{{ $materi->judul }}</h1>
        <p class="mt-3 text-lg text-redup sm:text-xl">{{ $materi->ringkasan }}</p>
        <p class="mt-3 inline-flex items-center gap-1.5 text-sm text-redup"><x-ikon nama="kalender" class="size-4" />Diperbarui {{ $materi->updated_at->format('d-m-Y H:i') }} WIB</p>

        @if ($materi->urlGambar())
            <img src="{{ $materi->urlGambar() }}" alt="{{ $materi->judul }}" decoding="async" class="mt-6 w-full rounded-2xl border border-garis-halus shadow-kartu">
        @endif

        @if ($materi->video_youtube)
            {{-- Pemutar YouTube baru dimuat saat tombol ditekan (lihat resources/js/edukasi/publik.js).
                 Tanpa JavaScript, tombol berfungsi sebagai tautan biasa ke YouTube. --}}
            <a href="https://www.youtube.com/watch?v={{ $materi->video_youtube }}" data-youtube="{{ $materi->video_youtube }}" data-judul="{{ $materi->judul }}" rel="noopener" class="group relative mt-6 flex aspect-video w-full flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl bg-navy p-4 text-center font-bold text-white shadow-kartu">
                {{-- Thumbnail ukuran sedang (320x180, sekitar 10 KB) agar hemat kuota. Jika gagal dimuat, latar navy tetap tampil. --}}
                <img src="https://i.ytimg.com/vi/{{ $materi->video_youtube }}/mqdefault.jpg" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover opacity-80" onerror="this.remove()">
                <span class="relative flex flex-col items-center gap-2 rounded-2xl bg-navy/70 px-5 py-4 transition-colors group-hover:bg-aksen">
                    <svg class="size-14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>
                    <span>Putar video</span>
                </span>
            </a>
        @endif

        @if (filled($materi->penjelasan))
            <x-kartu judul="Penjelasan" class="mt-6">
                <p class="whitespace-pre-line text-lg leading-relaxed">{{ $materi->penjelasan }}</p>
            </x-kartu>
        @endif

        @if ($materi->langkah->isNotEmpty())
            <section aria-labelledby="judul-langkah" class="mt-8">
                <div class="mb-4 flex items-center gap-3">
                    <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
                    <h2 id="judul-langkah" class="text-2xl font-extrabold text-navy">Langkah-langkah</h2>
                </div>
                {{-- Kartu langkah bernomor besar: mudah diikuti sambil praktik. --}}
                <ol class="space-y-4">
                    @foreach ($materi->langkah as $l)
                        <li class="flex gap-4 rounded-[0.9rem] border border-garis-halus bg-permukaan p-4 shadow-kartu sm:gap-5 sm:p-6">
                            <span class="font-judul text-4xl font-extrabold leading-none text-biru/80 sm:text-5xl" aria-hidden="true">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-extrabold leading-snug"><span class="sr-only">Langkah {{ $loop->iteration }}: </span>{{ $l->judul }}</h3>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $l->isi }}</p>
                                @if ($l->urlGambar())
                                    <img src="{{ $l->urlGambar() }}" alt="Gambar langkah {{ $loop->iteration }}: {{ $l->judul }}" loading="lazy" decoding="async" class="mt-3 w-full rounded-xl border border-garis-halus">
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- Navigasi antarbagian dalam satu kategori. Tidak menyimpan progres belajar: angkanya hanya letak bagian ini. --}}
        @if ($bagian['posisi'])
            @php
                $persen = (int) round($bagian['posisi'] / $bagian['total'] * 100);
                // Satu gaya dasar untuk semua kotak agar tinggi dan lebarnya selalu sama, apa pun panjang judulnya.
                $gayaKotak = 'flex h-full min-h-24 flex-col justify-center gap-1 rounded-[0.9rem] px-4 py-3 font-bold leading-snug transition-colors';
            @endphp
            {{-- Tiga kolom sama lebar. Di HP progres di atas, lalu dua tombol berdampingan. --}}
            <nav aria-label="Navigasi bagian" class="mt-10 grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3">
                <div class="order-first col-span-2 flex min-h-24 flex-col items-center justify-center text-center sm:order-2 sm:col-span-1">
                    <p class="text-sm text-redup">Bagian</p>
                    <p class="font-judul text-2xl font-extrabold leading-tight text-navy">{{ $bagian['posisi'] }}/{{ $bagian['total'] }}</p>
                    <div role="progressbar" aria-label="Letak bagian ini di kategori {{ $materi->kategori->nama }}" aria-valuemin="0" aria-valuemax="{{ $bagian['total'] }}" aria-valuenow="{{ $bagian['posisi'] }}" class="mt-2 h-2 w-full max-w-48 overflow-hidden rounded-full bg-garis-halus">
                        <div class="h-full rounded-full bg-aksen" style="width: {{ $persen }}%"></div>
                    </div>
                </div>

                @if ($bagian['sebelumnya'])
                    <a href="{{ route('edukasi.show', $bagian['sebelumnya']->slug) }}" rel="prev" class="{{ $gayaKotak }} border border-garis-halus bg-permukaan text-left shadow-kartu hover:border-biru sm:order-1">
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
                    <a href="{{ route('edukasi.show', $bagian['berikutnya']->slug) }}" rel="next" class="{{ $gayaKotak }} bg-biru text-right text-white hover:bg-biru-tua sm:order-3">
                        <span class="text-xs font-bold text-white/80">Bagian selanjutnya →</span>
                        <span class="line-clamp-2">{{ $bagian['berikutnya']->judul }}</span>
                    </a>
                @else
                    <a href="{{ route('edukasi.index', ['kategori' => $materi->kategori->slug]) }}" class="{{ $gayaKotak }} bg-biru text-right text-white hover:bg-biru-tua sm:order-3">
                        <span class="text-xs font-bold text-white/80">Bagian terakhir</span>
                        <span class="line-clamp-2">Kembali ke daftar</span>
                    </a>
                @endif
            </nav>

            <x-kartu :judul="'Semua bagian: '.$materi->kategori->nama" class="mt-6">
                <ol class="divide-y divide-garis-halus">
                    @foreach ($bagian['daftar'] as $b)
                        @php $sedangDibaca = $b->id === $materi->id; @endphp
                        <li>
                            <a href="{{ route('edukasi.show', $b->slug) }}" @class(['flex min-h-11 items-center gap-3 py-2 hover:underline', 'font-bold text-biru' => $sedangDibaca]) @if ($sedangDibaca) aria-current="page" @endif>
                                <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold', 'bg-biru text-white' => $sedangDibaca, 'bg-kosong-lembut text-redup' => ! $sedangDibaca])>{{ $loop->iteration }}</span>
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
