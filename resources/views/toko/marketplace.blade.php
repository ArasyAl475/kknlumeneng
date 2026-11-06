@extends('layouts.app')
@php $nama = \App\Services\Toko\DaftarMarketplace::nama($kunci); @endphp
@section('judul', 'Toko Desa di '.$nama)
@section('deskripsi', 'Produk Desa Lumeneng yang bisa dibeli lewat '.$nama.'.')

@section('isi')
    <a href="{{ route('toko.index') }}" class="tautan inline-flex min-h-11 items-center gap-1">
        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Pilih marketplace lain
    </a>

    <header class="mt-2 flex flex-wrap items-center gap-4 rounded-lg bg-kopi-tua p-4 text-white">
        @include('toko.partials.logo', ['kunci' => $kunci, 'besar' => true])
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $nama }}</h1>
            <p>Toko Desa Lumeneng di {{ $nama }}</p>
        </div>
        @if ($marketplace->url_toko)
            <a href="{{ $marketplace->url_toko }}" target="_blank" rel="noopener nofollow" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-white/70 px-4 font-bold hover:bg-kopi focus-visible:outline-white">
                Buka toko di {{ $nama }}@include('toko.partials.ikon-keluar')
            </a>
        @endif
    </header>

    @if ($produk->isEmpty())
        <x-kartu class="mt-4">
            <p class="font-bold">Belum ada produk di {{ $nama }}.</p>
            <p class="mt-1 text-redup">Coba marketplace lain, atau kembali lagi nanti.</p>
        </x-kartu>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($produk as $p)
                @include('toko.partials.kartu-produk', ['produk' => $p, 'kunci' => $kunci])
            @endforeach
        </ul>
    @endif

    @include('toko.partials.disclaimer')
@endsection
