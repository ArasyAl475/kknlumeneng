@extends('layouts.app')
@section('judul', 'Edukasi')
@section('deskripsi', 'Bacaan dan panduan praktik untuk warga Desa Lumeneng.')

@push('kepala')
    {{-- Latar hijau muda desain Stitch hanya untuk halaman ini; layout bersama tidak diubah. --}}
    <style>body { background-color: #eaffea; }</style>
@endpush

@php
    $adaSaringan = filled($cari) || filled($kategoriTerpilih);
    // Ukuran huruf dan bentuk mengikuti desain Stitch; tinggi minimal 44px dipertahankan untuk jempol.
    $gayaChip = 'inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-xs font-semibold tracking-[0.02em] transition-transform active:scale-95';
@endphp

@section('isi')
    <h1 class="sr-only">Edukasi</h1>

    <div class="flex flex-col gap-6">
        @if ($kategori->isNotEmpty() || $adaSaringan)
            <div class="flex flex-col gap-2">
                <form method="GET" action="{{ route('edukasi.index') }}" role="search" class="relative w-full">
                    @if (filled($kategoriTerpilih))
                        <input type="hidden" name="kategori" value="{{ $kategoriTerpilih }}">
                    @endif
                    <label for="cari" class="sr-only">Cari materi atau kategori</label>
                    {{-- Ikon kaca pembesar sekaligus tombol kirim, supaya tanpa tombol "Cari" tetap bisa dipakai di HP. --}}
                    <button type="submit" aria-label="Cari" class="absolute inset-y-0 left-0 flex w-12 items-center justify-center rounded-full text-outline">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </button>
                    <input id="cari" name="cari" type="search" enterkeyhint="search" value="{{ $cari }}" maxlength="100" placeholder="Cari panduan, materi, atau kategori..." class="h-12 w-full rounded-full border-0 bg-surface-container-lowest pl-11 pr-4 text-sm text-on-surface shadow-[0_2px_8px_-2px_rgba(26,46,31,0.06)] placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary-container">
                </form>

                {{-- Satu baris yang bisa digeser sampai ke tepi layar, supaya kategori bernama panjang tidak turun ke baris kedua. --}}
                <nav aria-label="Saring menurut kategori" class="-mx-4 flex items-center gap-1 overflow-x-auto px-4 py-1">
                    <a href="{{ route('edukasi.index', array_filter(['cari' => $cari])) }}" @class([$gayaChip, 'bg-primary-container text-on-primary shadow-sm' => blank($kategoriTerpilih), 'bg-surface-container text-on-surface-variant hover:text-on-surface' => filled($kategoriTerpilih)]) @if (blank($kategoriTerpilih)) aria-current="true" @endif>Semua</a>
                    @foreach ($kategori as $k)
                        <a href="{{ route('edukasi.index', array_filter(['kategori' => $k->slug, 'cari' => $cari])) }}" @class([$gayaChip, 'bg-primary-container text-on-primary shadow-sm' => $kategoriTerpilih === $k->slug, 'bg-surface-container text-on-surface-variant hover:text-on-surface' => $kategoriTerpilih !== $k->slug]) @if ($kategoriTerpilih === $k->slug) aria-current="true" @endif>{{ $k->nama }}</a>
                    @endforeach
                </nav>
            </div>
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
            <section class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold tracking-tight text-on-surface">Daftar Modul Praktik Lapangan</h2>
                    <span class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.04em] text-secondary">{{ $materi->total() }} modul aktif</span>
                </div>

                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($materi as $m)
                        <li>
                            <a href="{{ route('edukasi.show', $m->slug) }}" class="flex h-full flex-col overflow-hidden rounded-2xl bg-surface-container-lowest shadow-[0_2px_8px_-2px_rgba(26,46,31,0.06)] transition-shadow hover:shadow-md">
                                <span class="relative block aspect-video w-full bg-surface-container">
                                    @if ($m->urlThumbnail())
                                        <img src="{{ $m->urlThumbnail() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover">
                                    @else
                                        <span class="flex size-full items-center justify-center text-primary-container"><x-ikon nama="buku" class="size-10" /></span>
                                    @endif

                                    @if ($m->adaVideo())
                                        <span class="absolute inset-0 flex items-center justify-center bg-[#023616]/20 backdrop-blur-[1px]">
                                            <span class="flex size-12 items-center justify-center rounded-full bg-surface-container-lowest/90 text-primary-container shadow-lg">
                                                <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>
                                            </span>
                                        </span>
                                    @endif

                                    <span class="absolute bottom-3 left-3 flex flex-wrap gap-1.5 text-[10px] font-semibold tracking-[0.04em]">
                                        {{-- Nomor bagian hanya bermakna saat satu kategori dibaca berurutan tanpa pencarian. --}}
                                        @if (filled($kategoriTerpilih) && blank($cari))
                                            <span class="rounded-md bg-white/90 px-2 py-0.5 text-primary-container">Bagian {{ $materi->firstItem() + $loop->index }}</span>
                                        @endif
                                        @if ($m->adaVideo())
                                            <span class="rounded-md bg-[#213525]/80 px-2 py-0.5 text-[#dff7e0] backdrop-blur-sm">Ada video</span>
                                        @endif
                                        <span class="rounded-md bg-primary-fixed px-2 py-0.5 text-on-primary-fixed">{{ $m->kategori->nama }}</span>
                                    </span>
                                </span>

                                <span class="flex flex-1 flex-col gap-2 p-4">
                                    <span class="text-lg font-bold leading-snug text-on-surface">{{ $m->judul }}</span>
                                    <span class="line-clamp-2 flex-1 text-sm leading-5 text-on-surface-variant">{{ $m->ringkasan }}</span>
                                    {{-- Hanya tampilan tombol: seluruh kartu sudah satu tautan, jadi tidak ada tautan di dalam tautan. --}}
                                    <span class="mt-2 flex h-11 w-full items-center justify-center gap-1.5 rounded-xl bg-surface-container-low text-sm font-semibold text-primary-container">
                                        Mulai Belajar
                                        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($materi->hasPages())
                    <div>{{ $materi->links('edukasi.partials.halaman') }}</div>
                @endif
            </section>

            {{-- Tips sirkularitas: kotak terpisah di akhir daftar, hanya saat satu kategori dipilih.
                 Tampilan "Semua" sengaja tanpa tips agar tidak menumpuk. --}}
            @php
                $kategoriBertips = filled($kategoriTerpilih)
                    ? $kategori->where('slug', $kategoriTerpilih)->filter(fn ($k) => filled($k->tips_sirkular))
                    : collect();
            @endphp
            @if ($kategoriBertips->isNotEmpty())
                <section aria-labelledby="judul-tips" class="flex items-start gap-3 rounded-xl bg-surface-container-low p-4 text-on-surface">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-container text-on-primary">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3Z"/></svg>
                    </span>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <h2 id="judul-tips" class="text-xs font-bold text-[#023616]">Tips Sirkularitas</h2>
                        @foreach ($kategoriBertips as $k)
                            <p class="text-sm leading-relaxed text-on-surface-variant">{{ $k->tips_sirkular }}</p>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection
