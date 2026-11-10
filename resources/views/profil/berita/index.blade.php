@extends('layouts.app')
@section('judul', 'Berita Desa')
@section('deskripsi', 'Kabar kegiatan, pengumuman, dan artikel terbaru dari Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.')

@php
    $adaSaringan = filled($cari) || filled($jenisTerpilih);
    $pilihan = ['' => 'Semua'] + \App\Models\Profil\Berita::JENIS;
    // Berita teratas dibuat lebar hanya di halaman pertama tanpa saringan, agar terasa seperti halaman depan koran.
    $adaUtama = ! $adaSaringan && $berita->onFirstPage() && $berita->count() > 1;
@endphp

@section('hero')
    <x-pita-judul judul="Berita Desa Lumeneng" gambar="berita.hero" label="Kabar Desa" ikon="berita" :jejak="[['Berita', null]]"
        keterangan="Kegiatan warga, pengumuman penting, dan bacaan bermanfaat dari pemerintah desa." />
@endsection

@section('isi')
    <form method="GET" action="{{ route('berita.index') }}" role="search" class="mb-3 flex flex-wrap gap-2">
        @if (filled($jenisTerpilih))
            <input type="hidden" name="jenis" value="{{ $jenisTerpilih }}">
        @endif
        <label for="cari" class="sr-only">Cari berita</label>
        <input id="cari" name="cari" type="search" value="{{ $cari }}" maxlength="100" placeholder="Cari berita, misalnya: posyandu" class="isian min-w-0 flex-1 basis-56 rounded-xl">
        <x-tombol>Cari</x-tombol>
    </form>

    <nav aria-label="Saring menurut jenis" class="tanpa-batang-gulir mb-6 flex gap-2 overflow-x-auto pb-1">
        @foreach ($pilihan as $kunci => $label)
            @php $dipilih = (string) $jenisTerpilih === (string) $kunci; @endphp
            <a href="{{ route('berita.index', array_filter(['jenis' => $kunci, 'cari' => $cari])) }}" @if ($dipilih) aria-current="true" @endif
               @class([
                   'inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-full border-2 px-4 font-bold transition-colors',
                   'border-kopi bg-kopi text-white' => $dipilih,
                   'border-garis-halus bg-permukaan hover:border-kopi' => ! $dipilih,
               ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($berita->isEmpty())
        <x-kartu class="text-center">
            <x-ikon :nama="$adaSaringan ? 'kosong' : 'berita'" class="mx-auto size-10 text-kopi" />
            @if ($adaSaringan)
                <p class="mt-2 text-lg font-bold">Tidak ada berita yang cocok.</p>
                <p class="mt-1 text-redup">Coba kata lain atau lihat semua berita.</p>
                <x-tombol varian="kedua" :href="route('berita.index')" class="mt-3">Lihat semua berita</x-tombol>
            @else
                <p class="mt-2 text-lg font-bold">Belum ada berita.</p>
                <p class="mt-1 text-redup">Kabar dari desa akan tampil di sini. Silakan kembali lagi nanti.</p>
            @endif
        </x-kartu>
    @else
        @if (filled($cari))
            <p class="mb-3 text-redup" role="status">Menampilkan {{ $berita->total() }} hasil untuk "<span class="font-bold text-tinta">{{ $cari }}</span>".</p>
        @endif
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($berita as $b)
                <li @class(['sm:col-span-2 lg:col-span-3' => $adaUtama && $loop->first])>
                    @include('profil.partials.kartu-berita', ['berita' => $b, 'utama' => $adaUtama && $loop->first])
                </li>
            @endforeach
        </ul>
        @if ($berita->hasPages())
            <div class="mt-6">{{ $berita->links('profil.partials.halaman') }}</div>
        @endif
    @endif
@endsection
