@extends('layouts.app')
@section('judul', 'Edukasi')
@section('deskripsi', 'Bacaan dan panduan praktik untuk warga Desa Lumeneng.')

@php
    $adaSaringan = filled($cari) || filled($kategoriTerpilih);
    $gayaPilihan = 'inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-md border px-3 font-bold';
@endphp

@section('isi')
    <x-judul-halaman judul="Edukasi" keterangan="Bacaan dan panduan praktik untuk warga Desa Lumeneng." />

    @if ($kategori->isNotEmpty() || $adaSaringan)
        <form method="GET" action="{{ route('edukasi.index') }}" role="search" class="mb-3 flex flex-wrap gap-2">
            @if (filled($kategoriTerpilih))
                <input type="hidden" name="kategori" value="{{ $kategoriTerpilih }}">
            @endif
            <label for="cari" class="sr-only">Cari materi atau kategori</label>
            <input id="cari" name="cari" type="search" value="{{ $cari }}" maxlength="100" placeholder="Cari materi atau kategori" class="isian min-w-0 flex-1 basis-56">
            <x-tombol>Cari</x-tombol>
        </form>

        {{-- Satu baris yang bisa digeser, supaya kategori bernama panjang tidak turun ke baris kedua di HP. --}}
        <nav aria-label="Saring menurut kategori" class="mb-5 flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('edukasi.index', array_filter(['cari' => $cari])) }}" @class([$gayaPilihan, 'border-kopi bg-kopi text-white' => blank($kategoriTerpilih), 'border-garis bg-permukaan' => filled($kategoriTerpilih)]) @if (blank($kategoriTerpilih)) aria-current="true" @endif>Semua</a>
            @foreach ($kategori as $k)
                <a href="{{ route('edukasi.index', array_filter(['kategori' => $k->slug, 'cari' => $cari])) }}" @class([$gayaPilihan, 'border-kopi bg-kopi text-white' => $kategoriTerpilih === $k->slug, 'border-garis bg-permukaan' => $kategoriTerpilih !== $k->slug]) @if ($kategoriTerpilih === $k->slug) aria-current="true" @endif>{{ $k->nama }}</a>
            @endforeach
        </nav>
    @endif

    @if ($materi->isEmpty())
        <x-kartu class="text-center">
            <x-ikon :nama="$adaSaringan ? 'kosong' : 'buku'" class="mx-auto size-10 text-kopi" />
            @if ($adaSaringan)
                <p class="mt-2 text-lg font-bold">Tidak ada materi yang cocok.</p>
                <p class="mt-1 text-redup">Coba kata lain atau lihat semua materi.</p>
                <x-tombol varian="kedua" :href="route('edukasi.index')" class="mt-3">Lihat semua materi</x-tombol>
            @else
                <p class="mt-2 text-lg font-bold">Materi belum tersedia.</p>
                <p class="mt-1 text-redup">Materi sedang disiapkan. Silakan kembali lagi nanti.</p>
            @endif
        </x-kartu>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($materi as $m)
                <li>
                    <a href="{{ route('edukasi.show', $m->slug) }}" class="flex h-full flex-col overflow-hidden rounded-lg border border-garis-halus bg-permukaan hover:border-kopi">
                        @if ($m->urlThumbnail())
                            <img src="{{ $m->urlThumbnail() }}" alt="" loading="lazy" decoding="async" class="aspect-video w-full object-cover">
                        @else
                            <span class="flex aspect-video w-full items-center justify-center bg-kosong-lembut text-kopi"><x-ikon nama="buku" class="size-10" /></span>
                        @endif
                        <span class="flex flex-1 flex-col p-4">
                            <span class="text-sm font-bold text-redup">{{ $m->kategori->nama }}@if ($m->adaVideo()) · Ada video @endif</span>
                            <span class="mt-1 text-lg font-bold">{{ $m->judul }}</span>
                            <span class="mt-1 flex-1 text-redup">{{ $m->ringkasan }}</span>
                            {{-- Hanya tampilan tombol: seluruh kartu sudah satu tautan, jadi tidak ada tautan di dalam tautan. --}}
                            <span class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-kopi px-4 font-bold text-white">Mulai Belajar <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($materi->hasPages())
            <div class="mt-5">{{ $materi->links('edukasi.partials.halaman') }}</div>
        @endif

        {{-- Tips sirkularitas: kotak terpisah di akhir daftar, hanya saat satu kategori dipilih.
             Tampilan "Semua" sengaja tanpa tips agar tidak menumpuk. --}}
        @php
            $kategoriBertips = filled($kategoriTerpilih)
                ? $kategori->where('slug', $kategoriTerpilih)->filter(fn ($k) => filled($k->tips_sirkular))
                : collect();
        @endphp
        @if ($kategoriBertips->isNotEmpty())
            <section aria-labelledby="judul-tips" class="mt-6 rounded-lg border border-aman bg-aman-lembut p-4">
                <h2 id="judul-tips" class="flex items-center gap-2 text-lg font-bold text-aman">
                    <x-ikon nama="aman" class="size-6" /> Tips Sirkularitas
                </h2>
                <ul class="mt-2 space-y-2">
                    @foreach ($kategoriBertips as $k)
                        <li><span class="font-bold">{{ $k->nama }}:</span> {{ $k->tips_sirkular }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
@endsection
