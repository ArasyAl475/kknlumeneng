@extends('layouts.admin')
@section('judul', 'Toko Desa')

@section('isi')
    <x-judul-halaman judul="Toko Desa" keterangan="Pilih marketplace untuk mengelola produknya.">
        <x-tombol :href="route('admin.toko.produk.create')">Tambah produk</x-tombol>
    </x-judul-halaman>

    <ul class="grid gap-3 md:grid-cols-3">
        @foreach ($marketplace as $kunci => $m)
            @php $bertautan = $jumlahTautan[$kunci] ?? 0; @endphp
            <li>
                <a href="{{ route('admin.toko.marketplace.show', $kunci) }}" class="flex h-full flex-col gap-3 rounded-lg border border-garis-halus bg-permukaan p-4 hover:border-kopi">
                    <span class="flex items-center gap-3">
                        @include('toko.partials.logo', ['kunci' => $kunci])
                        <span class="min-w-0 flex-1 text-lg font-bold">{{ \App\Services\Toko\DaftarMarketplace::nama($kunci) }}</span>
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>{{ $bertautan }} dari {{ $jumlahProduk }} produk punya tautan</span>
                    <span class="text-sm text-redup">{{ $klik[$kunci] ?? 0 }} klik “Beli” dalam 30 hari terakhir</span>
                    <span class="flex flex-wrap gap-2">
                        <span data-warna="kosong" class="status-lembut rounded px-2 text-sm font-bold">Diisi manual</span>
                        @unless ($m->aktif)
                            <span data-warna="kuning" class="status-lembut inline-flex items-center gap-1 rounded px-2 text-sm font-bold"><x-ikon nama="kosong" class="size-4" />Disembunyikan dari situs</span>
                        @endunless
                    </span>
                </a>
            </li>
        @endforeach
    </ul>

    <x-kartu class="mt-4">
        <p class="font-bold">Tahap 1: produk diisi manual</p>
        <p class="mt-1 text-redup">Isi produk sekali, lalu tempel tautan tiap marketplace. Tarik data otomatis dari Shopee dan Tokopedia akan ditambahkan bila izin API mereka disetujui. Facebook tetap diisi manual.</p>
    </x-kartu>
@endsection
