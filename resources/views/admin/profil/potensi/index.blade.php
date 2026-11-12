@extends('layouts.admin')
@section('judul', 'Potensi & penghasil')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Potensi & penghasil desa" keterangan="Daerah apa, penghasil apa, letaknya di mana, dan fotonya. Tampil di halaman Profil Desa mengikuti angka urutan, dari kecil ke besar.">
        <x-tombol varian="kedua" :href="route('profil.index').'#potensi'" target="_blank" rel="noopener">Lihat di situs</x-tombol>
        <x-tombol :href="route('admin.profil.potensi.create')">Tambah potensi</x-tombol>
    </x-judul-halaman>

    @if ($potensi->isEmpty())
        <x-kartu>
            <p class="text-redup">Belum ada potensi desa. Tekan <span class="font-bold">Tambah potensi</span>, misalnya "Kebun kopi Dusun Sikembang".</p>
        </x-kartu>
    @else
        <x-kartu>
            <ul class="divide-y divide-garis-halus" data-paginasi="10">
                @foreach ($potensi as $p)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 basis-64 items-center gap-3">
                            @if ($p->urlFotoUtama())
                                <img src="{{ $p->urlFotoUtama() }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-xl object-cover">
                            @else
                                <span class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-kopi/10 text-kopi" aria-hidden="true"><x-ikon :nama="$p->ikonKategori()" class="size-7" /></span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-bold">{{ $p->nama }}</p>
                                <p class="text-sm text-redup">{{ $p->labelKategori() }}@if ($p->lokasi) · {{ $p->lokasi }}@endif · urutan {{ $p->urutan }} · {{ $p->fotos->count() }} foto</p>
                                @if ($p->hasil)
                                    <p class="mt-0.5 line-clamp-1 text-sm">Penghasil: {{ $p->hasil }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-tombol varian="kedua" :href="route('admin.profil.potensi.edit', $p)" aria-label="Ubah {{ $p->nama }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.profil.potensi.destroy', $p) }}" data-konfirmasi="Hapus {{ $p->nama }} dari Potensi Desa? Ini tidak bisa dibatalkan.">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" aria-label="Hapus {{ $p->nama }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-kartu>
    @endif
@endsection
