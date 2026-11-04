@extends('layouts.admin')
@section('judul', $wilayah->exists ? 'Ubah wilayah' : 'Tambah wilayah')
@push('skrip')
    @vite('resources/js/ews/admin-wilayah.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$wilayah->exists ? 'Ubah wilayah: '.$wilayah->nama : 'Tambah wilayah'" keterangan="Tekan Mulai menggambar, lalu ketuk titik-titik batas di peta. Minimal 3 titik dan sisinya tidak boleh menyilang." />

    <form method="POST" action="{{ $wilayah->exists ? route('admin.ews.wilayah.update', $wilayah) : route('admin.ews.wilayah.store') }}" class="space-y-4">
        @csrf
        @if ($wilayah->exists)
            @method('PUT')
            <p class="font-bold">ID alat: <span class="text-xl">{{ $wilayah->id }}</span></p>
        @endif

        <x-bidang nama="nama" label="Nama wilayah" required maxlength="100" :value="old('nama', $wilayah->nama)" />

        <div>
            <div class="mb-2 flex flex-wrap gap-2">
                <x-tombol type="button" varian="kedua" id="mulai-gambar">Mulai menggambar</x-tombol>
                <x-tombol type="button" varian="kedua" id="urungkan" hidden>Urungkan titik</x-tombol>
                <x-tombol type="button" varian="kedua" id="selesai-gambar" hidden>Selesai</x-tombol>
            </div>
            <p id="status-gambar" class="mb-2 text-sm font-bold" role="status"></p>
            <div id="peta-wilayah" class="relative z-0 h-96 rounded-lg border border-garis-halus bg-kosong-lembut" data-lainnya='@json($lainnya)' role="region" aria-label="Peta untuk menggambar batas wilayah. Koordinat juga bisa diisi manual di bawah."></div>
        </div>

        <details @if ($errors->has('poligon')) open @endif>
            <summary class="inline-flex min-h-11 items-center font-bold underline">Isi koordinat secara manual</summary>
            <label for="poligon" class="mt-2 block text-sm">Daftar titik [lintang, bujur], contoh: [[-7.17,109.55],[-7.17,109.56],[-7.18,109.56]]</label>
            <textarea id="poligon" name="poligon" rows="4" class="isian mt-1 font-mono text-sm" @error('poligon') aria-invalid="true" @enderror>{{ old('poligon', $wilayah->poligon ? json_encode($wilayah->poligon) : '') }}</textarea>
        </details>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan wilayah</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.ews.wilayah.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
