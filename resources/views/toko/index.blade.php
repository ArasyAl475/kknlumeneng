@extends('layouts.app')
@section('judul', 'Toko Desa')
@section('deskripsi', 'Kopi bubuk, baglog jamur, dan olahan jamur tiram dari Desa Lumeneng. Beli lewat Shopee, Tokopedia, atau Facebook.')

@push('kepala')
    @vite('resources/js/toko/publik.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Toko Desa Lumeneng" keterangan="Kopi, baglog dari limbah kulit kopi, dan olahan jamur tiram buatan warga. Produk kami sama di semua tempat; pilih marketplace yang paling Anda kenal." />

    @if ($marketplace->isEmpty())
        <section class="toko-kartu toko-bagian">
            <p class="font-semibold">Toko sedang disiapkan.</p>
            <p class="mt-1 text-redup">Belum ada marketplace yang dibuka. Silakan kembali lagi nanti.</p>
        </section>
    @else
        <ul class="grid gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($marketplace as $kunci => $m)
                <li>
                    <a href="{{ route('toko.marketplace', $kunci) }}" class="toko-kartu toko-kartu--tautan flex min-h-20 items-center gap-4 p-4">
                        @include('toko.partials.logo', ['kunci' => $kunci])
                        <span class="min-w-0 flex-1">
                            <span class="block text-lg font-semibold leading-snug">{{ \App\Services\Toko\DaftarMarketplace::nama($kunci) }}</span>
                            <span class="toko-angka block text-sm text-redup">{{ $jumlah[$kunci] > 0 ? $jumlah[$kunci].' produk' : 'Belum ada produk' }}</span>
                        </span>
                        <span class="toko-panah">
                            <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @include('toko.partials.disclaimer')
@endsection
