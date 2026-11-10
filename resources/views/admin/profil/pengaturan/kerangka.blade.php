{{-- Kerangka bersama empat formulir pengaturan profil. Halaman bagian mengisi @section('isian'). --}}
@extends('layouts.admin')
@section('judul', $info['judul'])
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$info['judul']" :keterangan="$info['keterangan']">
        <x-tombol varian="kedua" :href="route($ruteLihat ?? 'profil.index')" target="_blank" rel="noopener">Lihat di situs</x-tombol>
    </x-judul-halaman>

    <form method="POST" action="{{ route("admin.profil.{$bagian}.update") }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        @yield('isian')

        <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t border-garis-halus bg-kertas/95 px-4 py-3 backdrop-blur lg:mx-0 lg:rounded-2xl lg:border">
            <x-tombol>Simpan perubahan</x-tombol>
            <p class="text-sm text-redup">Perubahan langsung tampil di situs setelah disimpan.</p>
        </div>
    </form>
@endsection
