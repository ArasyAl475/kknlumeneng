@extends('layouts.app')
@php
    $namaMp = \App\Services\Toko\DaftarMarketplace::nama($kunci);
    $harga = $produk->hargaTeks();
    $fotoUtama = $produk->foto->first();
    $ringkasan = trim(($harga ? $harga.' · ' : '').$pengaturan['nama_penjual']);
@endphp
@section('judul', $produk->nama)
@section('deskripsi', $produk->nama.' dari Desa Lumeneng. '.($harga ? $harga.'. ' : '').'Beli lewat '.$namaMp.'.')

@push('kepala')
    @vite('resources/js/toko/publik.js')
    {{-- Agar tautan yang dibagikan di WhatsApp tampil dengan foto, nama, dan harga. --}}
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $produk->nama }}">
    <meta property="og:description" content="{{ $ringkasan }}">
    <meta property="og:url" content="{{ route('toko.produk', [$kunci, $produk]) }}">
    @if ($fotoUtama)
        <meta property="og:image" content="{{ $fotoUtama->url() }}">
    @endif
@endpush
@push('skrip')
    @vite('resources/js/toko/produk.js')
@endpush

@section('isi')
    <a href="{{ route('toko.marketplace', $kunci) }}" class="tautan inline-flex min-h-11 items-center gap-1">
        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Semua produk di {{ $namaMp }}
    </a>

    <div class="toko-sampul mt-2 flex items-center gap-3 px-4 py-2.5">
        @include('toko.partials.logo', ['kunci' => $kunci])
        <p class="font-semibold">{{ $namaMp }}</p>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2 md:items-start md:gap-6">
        {{-- 1. Galeri foto --}}
        <section aria-label="Foto produk" class="min-w-0">
            @if ($produk->foto->isEmpty())
                <div class="toko-kartu grid aspect-square place-items-center bg-kosong-lembut text-redup">
                    <p class="flex flex-col items-center gap-2"><x-ikon nama="toko" class="size-12" />Foto belum tersedia</p>
                </div>
            @else
                <div id="galeri" class="toko-kartu flex snap-x snap-mandatory overflow-x-auto" tabindex="0" aria-label="Geser untuk melihat foto lain">
                    @foreach ($produk->foto as $i => $foto)
                        <figure id="foto-{{ $i + 1 }}" class="w-full shrink-0 snap-center">
                            <img src="{{ $foto->urlKecil() }}" srcset="{{ $foto->urlKecil() }} 400w, {{ $foto->url() }} 1200w" sizes="(min-width: 768px) 50vw, 100vw"
                                width="1200" height="1200" alt="Foto {{ $i + 1 }} dari {{ $produk->foto->count() }}: {{ $produk->nama }}"
                                @if ($i > 0) loading="lazy" @endif decoding="async" class="aspect-square w-full object-contain">
                        </figure>
                    @endforeach
                </div>
                @if ($produk->foto->count() > 1)
                    <ul class="mt-2 flex gap-2 overflow-x-auto" aria-label="Pilih foto">
                        @foreach ($produk->foto as $i => $foto)
                            <li class="shrink-0">
                                <a href="#foto-{{ $i + 1 }}" data-foto="foto-{{ $i + 1 }}" class="block size-16 overflow-hidden rounded-lg border-2 border-garis-halus bg-permukaan hover:border-kopi">
                                    <img src="{{ $foto->urlKecil() }}" alt="Lihat foto {{ $i + 1 }}" width="64" height="64" loading="lazy" class="size-full object-cover">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>

        <div class="toko-kartu toko-bagian min-w-0 space-y-5">
            {{-- 2–3. Nama dan harga --}}
            <div>
                <h1 class="text-2xl font-bold leading-tight text-balance sm:text-3xl">{{ $produk->nama }}</h1>
                <p @class(['toko-angka mt-2 font-bold text-kopi', 'text-2xl' => $harga, 'text-lg' => ! $harga])>{{ $harga ?? 'Lihat harga di marketplace' }}</p>
            </div>

            {{-- 4. Varian --}}
            @if ($produk->varian->isNotEmpty())
                <section aria-labelledby="judul-varian">
                    <h2 id="judul-varian" class="text-sm font-semibold text-redup">Pilihan</h2>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($produk->varian as $v)
                            <li class="flex min-h-11 flex-col justify-center rounded-xl border border-garis-halus px-3.5 py-1.5 {{ $v->tersedia ? 'bg-permukaan' : 'bg-kosong-lembut text-redup' }}">
                                <span class="font-semibold">{{ $v->nama }}</span>
                                <span class="toko-angka text-sm">
                                    {{ $v->harga !== null ? \App\Models\Toko\Produk::rupiah($v->harga) : 'Harga di marketplace' }}@unless ($v->tersedia) · habis @endunless
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Status stok: selalu ikon + kata, bukan warna saja --}}
            @if ($produk->adaStok())
                <p data-warna="hijau" class="status-lembut toko-pil"><x-ikon nama="aman" class="size-4" />Tersedia</p>
            @else
                <p data-warna="kosong" class="status-lembut toko-pil"><x-ikon nama="kosong" class="size-4" />Stok habis</p>
            @endif

            {{-- 5. Tombol beli --}}
            <section aria-label="Beli">
                @if ($beli)
                    <x-tombol :href="route('toko.beli', [$kunci, $produk])" target="_blank" rel="noopener nofollow" class="w-full text-lg">
                        {{ $beli['jenis'] === \App\Services\Toko\TautanBeli::JENIS_PRODUK ? 'Beli di '.$namaMp : 'Kunjungi toko di '.$namaMp }}@include('toko.partials.ikon-keluar')
                    </x-tombol>
                    @if ($beli['jenis'] === \App\Services\Toko\TautanBeli::JENIS_TOKO)
                        <p class="mt-1 text-sm text-redup">Cari “{{ $produk->nama }}” di toko kami setelah halaman {{ $namaMp }} terbuka.</p>
                    @endif
                @else
                    <p class="rounded-xl bg-kosong-lembut p-3 font-semibold">Produk ini belum tersedia di {{ $namaMp }}.</p>
                @endif

                @if ($lainnya->isNotEmpty())
                    <p class="mt-4 text-sm font-semibold text-redup">{{ $beli ? 'Juga tersedia di:' : 'Tersedia di:' }}</p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($lainnya as $kunciLain => $tujuan)
                            <x-tombol varian="kedua" :href="route('toko.beli', [$kunciLain, $produk])" target="_blank" rel="noopener nofollow">
                                {{ \App\Services\Toko\DaftarMarketplace::nama($kunciLain) }}@include('toko.partials.ikon-keluar', ['kelas' => 'size-4'])
                            </x-tombol>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- 6. Pengiriman --}}
            <section aria-labelledby="judul-kirim" class="border-t border-garis-halus pt-4">
                <h2 id="judul-kirim" class="font-semibold">Pengiriman</h2>
                <p class="mt-1 flex items-start gap-2"><x-ikon nama="lokasi" class="mt-0.5" />Dikirim dari {{ $pengaturan['lokasi_kirim'] }}</p>
                <p class="mt-1 text-sm text-redup">Ongkos kirim dihitung di marketplace saat membayar.</p>
            </section>
        </div>
    </div>

    {{-- 7. Spesifikasi Produk: tabel ala Shopee; Stok dan Dikirim Dari selalu ada --}}
    <section aria-labelledby="judul-spesifikasi" class="toko-kartu toko-bagian mt-6">
        <h2 id="judul-spesifikasi" class="toko-bagian__judul">Spesifikasi Produk</h2>
        <dl class="divide-y divide-garis-halus">
            @foreach ($produk->spesifikasiPublik($pengaturan['lokasi_kirim']) as $baris)
                <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-x-4 py-2.5 first:pt-0 last:pb-0">
                    <dt class="text-redup">{{ $baris['label'] }}</dt>
                    <dd class="font-semibold">{{ $baris['nilai'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- 8. Deskripsi --}}
    @if ($produk->deskripsi)
        <section aria-labelledby="judul-deskripsi" class="toko-kartu toko-bagian mt-4">
            <h2 id="judul-deskripsi" class="toko-bagian__judul">Deskripsi</h2>
            <p class="max-w-prose whitespace-pre-line leading-relaxed">{{ $produk->deskripsi }}</p>
        </section>
    @endif

    {{-- 9. Penjual --}}
    <section class="toko-kartu toko-bagian mt-4">
        <div class="flex items-start gap-3">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-kopi text-white"><x-ikon nama="toko" /></span>
            <div class="min-w-0">
                <h2 class="font-semibold">{{ $pengaturan['nama_penjual'] }}</h2>
                @if ($pengaturan['keterangan_penjual'])
                    <p class="text-redup">{{ $pengaturan['keterangan_penjual'] }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- 10. Produk lain --}}
    @if ($produkLain->isNotEmpty())
        <section aria-labelledby="judul-lain" class="mt-8">
            <h2 id="judul-lain" class="text-xl font-bold">Produk lain</h2>
            <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($produkLain as $p)
                    @include('toko.partials.kartu-produk', ['produk' => $p, 'kunci' => $kunci])
                @endforeach
            </ul>
        </section>
    @endif

    @include('toko.partials.disclaimer')
@endsection
