@extends('layouts.admin')
@php $nama = \App\Services\Toko\DaftarMarketplace::nama($kunci); @endphp
@section('judul', 'Toko di '.$nama)

@section('isi')
    <a href="{{ route('admin.toko.beranda') }}" class="tautan inline-flex min-h-11 items-center gap-1">
        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Kembali ke Toko Desa
    </a>

    <header class="mt-2 flex flex-wrap items-center gap-4 rounded-lg bg-kopi-tua p-4 text-white">
        @include('toko.partials.logo', ['kunci' => $kunci, 'besar' => true])
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $nama }}</h1>
            <p>Kelola produk di {{ $nama }}</p>
        </div>
    </header>

    <div class="mt-4 flex flex-wrap gap-2">
        <span data-warna="kosong" class="status-lembut rounded px-2 py-1 text-sm font-bold">Sumber data: diisi manual</span>
        <span data-warna="kuning" class="status-lembut rounded px-2 py-1 text-sm font-bold">Sinkron otomatis: belum tersambung</span>
        @unless ($marketplace->aktif)
            <span data-warna="kuning" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="kosong" class="size-4" />Disembunyikan dari situs</span>
        @endunless
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        @if ($marketplace->url_dasbor)
            <x-tombol :href="$marketplace->url_dasbor" target="_blank" rel="noopener">Buka dasbor penjual {{ $nama }}@include('toko.partials.ikon-keluar')</x-tombol>
        @endif
        <x-tombol varian="kedua" :href="route('toko.marketplace', $kunci)" target="_blank">Lihat halaman di situs@include('toko.partials.ikon-keluar')</x-tombol>
        <x-tombol varian="kedua" type="button" disabled class="cursor-not-allowed border-dashed opacity-70">Sinkron otomatis (menunggu izin API)</x-tombol>
    </div>

    <x-kartu class="mt-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-bold">Produk di {{ $nama }}</h2>
            <x-tombol varian="kedua" :href="route('admin.toko.produk.create', ['kembali' => $kunci])">Tambah produk</x-tombol>
        </div>

        @if ($produk->isEmpty())
            <p class="text-redup">Belum ada produk. Tambahkan produk pertama, lalu tempel tautan {{ $nama }}-nya.</p>
        @endif

        <ul class="divide-y divide-garis-halus">
            @foreach ($produk as $p)
                @php
                    $tautan = $p->tautanUntuk($kunci);
                    $beli = \App\Services\Toko\TautanBeli::untuk($p, $marketplace);
                    $fotoUtama = $p->foto->first();
                @endphp
                <li class="flex flex-wrap items-start gap-3 py-3 first:pt-0 last:pb-0">
                    @if ($fotoUtama)
                        <img src="{{ $fotoUtama->urlKecil() }}" alt="" width="64" height="64" loading="lazy" class="size-16 shrink-0 rounded object-cover">
                    @else
                        <span class="grid size-16 shrink-0 place-items-center rounded bg-kosong-lembut text-redup"><x-ikon nama="toko" /><span class="sr-only">Belum ada foto</span></span>
                    @endif
                    <div class="min-w-0 flex-1 basis-52">
                        <p class="font-bold">{{ $p->nama }}</p>
                        <p class="text-sm">{{ $p->hargaTeks() ?? 'Harga belum diisi' }}</p>
                        <p class="mt-1 flex flex-wrap gap-2 text-sm font-bold">
                            @if ($tautan)
                                <span data-warna="hijau" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="aman" class="size-4" />Tautan {{ $nama }} terisi</span>
                            @elseif ($beli)
                                <span data-warna="kuning" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="waspada" class="size-4" />Memakai alamat toko</span>
                            @else
                                <span data-warna="kosong" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="kosong" class="size-4" />Tautan {{ $nama }} kosong</span>
                            @endif
                            @if ($p->tampil)
                                <span data-warna="hijau" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="aman" class="size-4" />Tampil di situs</span>
                            @else
                                <span data-warna="kosong" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="kosong" class="size-4" />Disembunyikan</span>
                            @endif
                        </p>
                        @if ($tautan)
                            <p class="mt-1 break-all text-sm text-redup">{{ $tautan }}</p>
                        @endif
                    </div>
                    <x-tombol varian="kedua" :href="route('admin.toko.produk.edit', ['produk' => $p, 'kembali' => $kunci])" aria-label="Ubah {{ $p->nama }}">Ubah</x-tombol>
                </li>
            @endforeach
        </ul>
    </x-kartu>

    <details class="mt-4 rounded-lg border border-garis-halus bg-permukaan" @if ($errors->any()) open @endif>
        <summary class="flex min-h-11 items-center px-4 font-bold">Alamat toko dan dasbor {{ $nama }}</summary>
        <form method="POST" action="{{ route('admin.toko.marketplace.update', $kunci) }}" class="space-y-4 px-4 pb-4">
            @csrf
            @method('PUT')
            <x-bidang nama="url_toko" label="Alamat toko (untuk pengunjung)" inputmode="url" maxlength="500"
                :value="old('url_toko', $marketplace->url_toko)" :placeholder="'https://'.\App\Services\Toko\DaftarMarketplace::domainUtama($kunci).'/nama-toko'"
                bantuan="Halaman toko yang bisa dibuka siapa saja. Dipakai bila produk belum punya tautan sendiri. Jangan isi dengan halaman pengaturan akun atau riwayat belanja." />
            <x-bidang nama="url_dasbor" label="Alamat dasbor penjual (untuk admin)" inputmode="url" maxlength="500"
                :value="old('url_dasbor', $marketplace->url_dasbor)" bantuan="Halaman tempat admin mengelola pesanan di {{ $nama }}. Hanya tampil di panel admin." />
            <input type="hidden" name="aktif" value="0">
            <label for="aktif" class="flex min-h-11 items-center gap-3 font-bold">
                <input type="checkbox" id="aktif" name="aktif" value="1" class="size-6" @checked(old('aktif', $marketplace->aktif))>
                Tampilkan {{ $nama }} di situs
            </label>
            <x-tombol>Simpan</x-tombol>
        </form>
    </details>
@endsection
