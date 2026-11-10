@extends('layouts.admin')
@section('judul', 'Lembaga desa')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Lembaga desa" keterangan="Tampil di bagian bawah halaman Pemerintahan Desa, misalnya BPD, PKK, dan Karang Taruna.">
        <x-tombol varian="kedua" :href="route('profil.index').'#pemerintahan'" target="_blank" rel="noopener">Lihat di situs</x-tombol>
        <x-tombol :href="route('admin.profil.lembaga.create')">Tambah lembaga</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        @if ($lembaga->isEmpty())
            <p class="text-redup">Belum ada lembaga. Tekan <span class="font-bold">Tambah lembaga</span> untuk menambahkan.</p>
        @endif
        <ul class="divide-y divide-garis-halus" data-paginasi="10">
            @foreach ($lembaga as $l)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                        <span class="flex min-w-12 shrink-0 items-center justify-center rounded-xl bg-kopi/10 px-2 py-2.5 text-sm font-bold text-kopi" aria-hidden="true">{{ $l->sebutan() }}</span>
                        <div class="min-w-0">
                            <p class="font-bold">{{ $l->nama }}</p>
                            <p class="text-sm text-redup">{{ $l->ketua ? 'Ketua: '.$l->ketua : 'Ketua belum diisi' }} · urutan {{ $l->urutan }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.profil.lembaga.edit', $l)" aria-label="Ubah {{ $l->nama }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.profil.lembaga.destroy', $l) }}" data-konfirmasi="Hapus lembaga {{ $l->nama }}? Ini tidak bisa dibatalkan.">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $l->nama }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-kartu>
@endsection
