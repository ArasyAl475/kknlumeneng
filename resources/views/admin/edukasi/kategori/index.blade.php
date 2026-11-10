@extends('layouts.admin')
@section('judul', 'Kategori materi')
@push('skrip')
    @vite('resources/js/edukasi/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Kategori materi" keterangan="Kategori memudahkan warga menemukan materi, misalnya Pengolahan Limbah Kopi, Baglog, atau Jamur.">
        <x-tombol :href="route('admin.edukasi.kategori.create')">Tambah kategori</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        @if ($kategori->isEmpty())
            <p class="text-redup">Belum ada kategori. Tambahkan kategori pertama sebelum menulis materi.</p>
        @endif
        <ul class="divide-y divide-garis-halus" data-paginasi="10">
            @foreach ($kategori as $k)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1 basis-56">
                        <p class="font-bold">{{ $k->nama }}</p>
                        <p class="text-sm text-redup">{{ $k->materi_count }} materi</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.edukasi.kategori.edit', $k)" aria-label="Ubah {{ $k->nama }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.edukasi.kategori.destroy', $k) }}" data-konfirmasi="Hapus kategori {{ $k->nama }}?">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $k->nama }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-kartu>
@endsection
