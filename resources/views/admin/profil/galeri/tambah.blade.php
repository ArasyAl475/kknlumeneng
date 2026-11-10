@extends('layouts.admin')
@section('judul', 'Tambah foto desa')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Tambah foto desa" keterangan="Untuk foto suasana desa, misalnya panen, pemandangan, atau kegiatan warga yang tidak dibuat beritanya.">
        <x-tombol varian="kedua" :href="route('admin.profil.galeri.index')">Kembali ke galeri</x-tombol>
    </x-judul-halaman>

    <div role="note" class="mb-4 flex items-start gap-3 rounded-2xl border-2 border-kopi/30 bg-permukaan p-4">
        <x-ikon nama="berita" class="mt-0.5 size-5 shrink-0 text-kopi" />
        <p>Punya foto dari kegiatan yang dibuat beritanya? Unggah saja di formulir berita (bagian <span class="font-bold">Foto dokumentasi kegiatan</span>). Fotonya otomatis masuk galeri.</p>
    </div>

    <x-kartu>
        <form method="POST" action="{{ route('admin.profil.galeri.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @include('admin.profil.partials.foto-banyak', ['nama' => 'foto', 'label' => 'Pilih foto', 'wajib' => true])
            <x-bidang nama="judul" label="Judul (boleh dikosongkan)" maxlength="150" :value="old('judul')" bantuan="Dipakai untuk semua foto yang dipilih. Contoh: Panen padi di Dusun Krajan." />
            @include('admin.profil.partials.area', ['nama' => 'keterangan', 'label' => 'Keterangan (boleh dikosongkan)', 'nilai' => old('keterangan'), 'baris' => 3, 'maks' => 500, 'bantuan' => 'Cerita singkat tentang foto: siapa, sedang apa, di mana.'])
            <x-bidang nama="tanggal" label="Tanggal foto (boleh dikosongkan)" type="date" :max="now()->toDateString()" :value="old('tanggal')" class="max-w-xs" />
            <x-tombol>Unggah ke galeri</x-tombol>
        </form>
    </x-kartu>
@endsection
