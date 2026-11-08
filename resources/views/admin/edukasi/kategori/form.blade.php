@extends('layouts.admin')
@section('judul', $kategori->exists ? 'Ubah kategori' : 'Tambah kategori')

@section('isi')
    <x-judul-halaman :judul="$kategori->exists ? 'Ubah kategori: '.$kategori->nama : 'Tambah kategori'" />

    <x-kartu>
        <form method="POST" action="{{ $kategori->exists ? route('admin.edukasi.kategori.update', $kategori) : route('admin.edukasi.kategori.store') }}" class="space-y-4">
            @csrf
            @if ($kategori->exists)
                @method('PUT')
            @endif

            <x-bidang nama="nama" label="Nama kategori" required maxlength="100" :value="old('nama', $kategori->nama)" />

            @include('admin.edukasi.partials.area', ['nama' => 'tips_sirkular', 'label' => 'Tips sirkularitas', 'nilai' => old('tips_sirkular', $kategori->tips_sirkular), 'baris' => 3, 'maks' => 500, 'bantuan' => 'Boleh dikosongkan. Satu atau dua kalimat tentang cara memanfaatkan kembali limbah, tampil di halaman Edukasi untuk warga.'])

            <div class="flex flex-wrap gap-2">
                <x-tombol>Simpan kategori</x-tombol>
                <x-tombol varian="kedua" :href="route('admin.edukasi.kategori.index')">Batal</x-tombol>
            </div>
        </form>
    </x-kartu>
@endsection
