@extends('layouts.admin')
@section('judul', 'Galeri foto')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@php $pilihan = ['' => 'Semua'] + \App\Models\Profil\Foto::JENIS; @endphp

@section('isi')
    <x-judul-halaman judul="Galeri foto" keterangan="Foto dari berita masuk ke sini otomatis. Tombol Tambah foto desa hanya untuk foto yang tidak dibuat beritanya. Foto dari berita berstatus draf belum terlihat warga.">
        <x-tombol varian="kedua" :href="route('galeri.index')" target="_blank" rel="noopener">Lihat di situs</x-tombol>
        <x-tombol :href="route('admin.profil.galeri.create')">Tambah foto desa</x-tombol>
    </x-judul-halaman>

    <nav aria-label="Saring foto" class="tanpa-batang-gulir mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ($pilihan as $kunci => $label)
            @php $dipilih = (string) $jenisTerpilih === (string) $kunci; @endphp
            <a href="{{ route('admin.profil.galeri.index', array_filter(['jenis' => $kunci])) }}" @if ($dipilih) aria-current="true" @endif
               @class(['inline-flex min-h-11 shrink-0 items-center rounded-full border-2 px-4 font-bold', 'border-kopi bg-kopi text-white' => $dipilih, 'border-garis-halus bg-permukaan hover:border-kopi' => ! $dipilih])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($foto->isEmpty())
        <x-kartu>
            <p class="text-redup">Belum ada foto di sini. Foto dari berita akan muncul otomatis. Untuk foto desa lainnya, tekan <span class="font-bold">Tambah foto desa</span>.</p>
        </x-kartu>
    @else
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($foto as $f)
                <li class="flex flex-col overflow-hidden rounded-2xl border border-garis-halus bg-permukaan shadow-sm">
                    <img src="{{ $f->urlGambar() }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                    <div class="flex flex-1 flex-col p-3">
                        <p class="line-clamp-2 font-bold leading-snug">{{ $f->judulTampil() }}</p>
                        @if ($f->terhubung())
                            <p class="mt-1 flex items-start gap-1 text-sm text-kopi">
                                <x-ikon nama="berita" class="mt-0.5 size-4" />
                                <a href="{{ route('admin.profil.berita.edit', $f->berita_id) }}" class="line-clamp-2 underline">{{ $f->berita?->judul }}</a>
                            </p>
                            @unless ($f->berita?->sudahTerbit())
                                <p class="mt-1 text-xs font-bold text-redup">Berita masih draf</p>
                            @endunless
                        @else
                            <p class="mt-1 flex items-center gap-1 text-sm text-redup"><x-ikon nama="galeri" class="size-4" /> Foto desa (mandiri)</p>
                        @endif
                        <div class="mt-auto flex gap-2 pt-3">
                            <x-tombol varian="kedua" :href="route('admin.profil.galeri.edit', $f)" class="flex-1 px-2" aria-label="Ubah {{ $f->judulTampil() }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.profil.galeri.destroy', $f) }}" data-konfirmasi="Hapus foto ini dari galeri? Ini tidak bisa dibatalkan." class="flex-1">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" class="w-full px-2" aria-label="Hapus {{ $f->judulTampil() }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($foto->hasPages())
            <div class="mt-5">{{ $foto->links('profil.partials.halaman') }}</div>
        @endif
    @endif
@endsection
