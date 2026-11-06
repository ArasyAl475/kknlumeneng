@extends('layouts.app')
@section('judul', 'Toko Desa')
@section('deskripsi', 'Kopi bubuk, baglog jamur, dan olahan jamur tiram dari Desa Lumeneng. Beli lewat Shopee, Tokopedia, atau Facebook.')

@section('isi')
    <x-judul-halaman judul="Toko Desa Lumeneng" keterangan="Kopi, baglog dari limbah kulit kopi, dan olahan jamur tiram buatan warga. Produk kami sama di semua tempat; pilih marketplace yang paling Anda kenal." />

    @if ($marketplace->isEmpty())
        <x-kartu>
            <p class="font-bold">Toko sedang disiapkan.</p>
            <p class="mt-1 text-redup">Belum ada marketplace yang dibuka. Silakan kembali lagi nanti.</p>
        </x-kartu>
    @else
        <ul class="grid gap-3 sm:grid-cols-3">
            @foreach ($marketplace as $kunci => $m)
                <li>
                    <a href="{{ route('toko.marketplace', $kunci) }}" class="flex min-h-20 items-center gap-3 rounded-lg border border-garis-halus bg-permukaan p-4 hover:border-kopi">
                        @include('toko.partials.logo', ['kunci' => $kunci])
                        <span class="min-w-0 flex-1">
                            <span class="block text-lg font-bold">{{ \App\Services\Toko\DaftarMarketplace::nama($kunci) }}</span>
                            <span class="text-redup">{{ $jumlah[$kunci] > 0 ? $jumlah[$kunci].' produk' : 'Belum ada produk' }}</span>
                        </span>
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @include('toko.partials.disclaimer')
@endsection
