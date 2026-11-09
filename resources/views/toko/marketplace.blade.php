@extends('layouts.app')
@php $nama = \App\Services\Toko\DaftarMarketplace::nama($kunci); @endphp
@section('judul', 'Toko Desa di '.$nama)
@section('deskripsi', 'Produk Desa Lumeneng yang bisa dibeli lewat '.$nama.'.')

@push('kepala')
    @vite('resources/js/toko/publik.js')
@endpush

@section('hero')
    <x-pita-judul :judul="$nama" gambar="toko.hero" label="Toko Desa Lumeneng di" ikon="toko" :jejak="[['Toko Desa', route('toko.index')], [$nama, null]]" keterangan="Produk warga Desa Lumeneng yang bisa dibeli lewat {{ $nama }}.">
        @include('toko.partials.logo', ['kunci' => $kunci, 'besar' => true])
        @if ($marketplace->url_toko)
            <x-tombol varian="terang" :href="$marketplace->url_toko" target="_blank" rel="noopener nofollow">
                Buka toko di {{ $nama }}@include('toko.partials.ikon-keluar')
            </x-tombol>
        @endif
    </x-pita-judul>
@endsection

@section('isi')

    @if ($produk->isEmpty())
        <section class="toko-kartu toko-bagian">
            <p class="font-semibold">Belum ada produk di {{ $nama }}.</p>
            <p class="mt-1 text-redup">Coba marketplace lain, atau kembali lagi nanti.</p>
        </section>
    @else
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4" data-paginasi="12">
            @foreach ($produk as $p)
                @include('toko.partials.kartu-produk', ['produk' => $p, 'kunci' => $kunci])
            @endforeach
        </ul>
    @endif

    @include('toko.partials.disclaimer')
@endsection
