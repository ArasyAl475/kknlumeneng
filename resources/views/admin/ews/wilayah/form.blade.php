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
        @endif

        <x-bidang nama="id_alat" label="ID alat" type="number" min="1" max="999999" inputmode="numeric" class="max-w-xs" :value="old('id_alat', $idAlatSaran)"
                  bantuan="Angka ini dipasang di ESP32 sebagai WILAYAH_ID (dan di topik MQTT ews/ANGKA/sensor). {{ $wilayah->exists ? 'Bila diganti, alat yang masih memakai angka lama ditolak sampai kodenya disamakan.' : 'Sudah terisi angka bebas terkecil; ganti bila angka di alat berbeda.' }}" />

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
            <summary class="inline-flex min-h-11 items-center font-bold underline">Isi batas daerah dengan koordinat</summary>
            <label for="poligon" class="mt-2 block text-sm">Satu titik per baris dalam bentuk "lintang, bujur" (minimal 3 baris). Format lain yang diterima: [[-7.17,109.55],[-7.17,109.56],[-7.18,109.56]]</label>
            <textarea id="poligon" name="poligon" rows="5" class="isian mt-1 font-mono text-sm" @error('poligon') aria-invalid="true" aria-describedby="poligon-galat" @enderror>{{ old('poligon', $wilayah->poligon ? json_encode($wilayah->poligon) : '') }}</textarea>
            @error('poligon')
                <p id="poligon-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
            @enderror
        </details>

        @include('admin.ews.partials.lokasi-titik', [
            'judul' => 'Lokasi alat EWS (opsional)',
            'bantuan' => 'Tempat alat ESP32 dipasang di wilayah ini; tampil sebagai penanda di peta warga.',
            'wajib' => false,
            'lintang' => $wilayah->lintang,
            'bujur' => $wilayah->bujur,
        ])

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan wilayah</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.ews.wilayah.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
