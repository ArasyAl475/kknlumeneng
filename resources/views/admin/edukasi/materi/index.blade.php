@extends('layouts.admin')
@section('judul', 'Daftar materi')
@push('skrip')
    @vite('resources/js/edukasi/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Daftar materi" keterangan="Warga hanya melihat materi berstatus Terbit.">
        <x-tombol :href="route('admin.edukasi.materi.create')">Tambah materi</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        @if ($materi->isEmpty())
            <p class="text-redup">Belum ada materi. Tekan Tambah materi untuk menulis yang pertama.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($materi as $m)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1 basis-56">
                        <p class="font-bold">{{ $m->judul }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            @include('admin.edukasi.partials.status', ['materi' => $m])
                            <span class="text-redup">{{ $m->kategori->nama }} · Bagian {{ $m->urutan }} · {{ $m->langkah_count }} langkah</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.edukasi.materi.edit', $m)" aria-label="Ubah {{ $m->judul }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.edukasi.materi.destroy', $m) }}" data-konfirmasi="Hapus materi {{ $m->judul }} beserta semua langkah dan gambarnya? Ini tidak bisa dibatalkan.">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $m->judul }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($materi->hasPages())
            <div class="mt-4">{{ $materi->links('edukasi.partials.halaman') }}</div>
        @endif
    </x-kartu>
@endsection
