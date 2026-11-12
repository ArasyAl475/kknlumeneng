@extends('layouts.app')
@section('judul', 'Galeri Desa')
@section('deskripsi', 'Foto kegiatan warga dan suasana Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.')

@php $pilihan = ['' => 'Semua foto'] + \App\Models\Profil\Foto::JENIS; @endphp

@push('skrip')
    @vite('resources/js/profil/galeri.js')
@endpush

@section('hero')
    <x-pita-judul judul="Galeri Desa Lumeneng" gambar="galeri.hero" label="Galeri" ikon="galeri" :jejak="[['Galeri', null]]"
        keterangan="Dokumentasi kegiatan warga dan potret suasana desa. Ketuk foto untuk memperbesar, lalu geser untuk melihat foto lain." />
@endsection

@section('isi')
    <nav aria-label="Saring foto" class="tanpa-batang-gulir mb-6 flex gap-2 overflow-x-auto pb-1">
        @foreach ($pilihan as $kunci => $label)
            @php $dipilih = (string) $jenisTerpilih === (string) $kunci; @endphp
            <a href="{{ route('galeri.index', array_filter(['jenis' => $kunci])) }}" @if ($dipilih) aria-current="true" @endif
               @class([
                   'inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-full border-2 px-4 font-bold transition-colors',
                   'border-kopi bg-kopi text-white' => $dipilih,
                   'border-garis-halus bg-permukaan hover:border-kopi' => ! $dipilih,
               ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($foto->isEmpty())
        <x-kartu class="text-center">
            <x-ikon nama="galeri" class="mx-auto size-10 text-kopi" />
            <p class="mt-2 text-lg font-bold">{{ $jenisTerpilih ? 'Belum ada foto di bagian ini.' : 'Galeri masih kosong.' }}</p>
            <p class="mt-1 text-redup">Foto kegiatan desa akan tampil di sini. Silakan kembali lagi nanti.</p>
            @if ($jenisTerpilih)
                <x-tombol varian="kedua" :href="route('galeri.index')" class="mt-3">Lihat semua foto</x-tombol>
            @endif
        </x-kartu>
    @else
        <ul data-galeri class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($foto as $f)
                <li>@include('profil.partials.kotak-foto', ['foto' => $f])</li>
            @endforeach
        </ul>
        @if ($foto->hasPages())
            <div class="mt-6">{{ $foto->links('profil.partials.halaman') }}</div>
        @endif
    @endif
@endsection
