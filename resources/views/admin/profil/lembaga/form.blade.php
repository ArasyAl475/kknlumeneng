@extends('layouts.admin')
@section('judul', $lembaga->exists ? 'Ubah lembaga' : 'Tambah lembaga')

@section('isi')
    <x-judul-halaman :judul="$lembaga->exists ? 'Ubah: '.$lembaga->nama : 'Tambah lembaga desa'">
        <x-tombol varian="kedua" :href="route('admin.profil.lembaga.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        <form method="POST" action="{{ $lembaga->exists ? route('admin.profil.lembaga.update', $lembaga) : route('admin.profil.lembaga.store') }}" class="space-y-4">
            @csrf
            @if ($lembaga->exists)
                @method('PUT')
            @endif

            <x-bidang nama="nama" label="Nama lembaga" required maxlength="100" :value="old('nama', $lembaga->nama)" bantuan="Contoh: Badan Permusyawaratan Desa." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-bidang nama="singkatan" label="Singkatan (boleh dikosongkan)" maxlength="20" :value="old('singkatan', $lembaga->singkatan)" bantuan="Contoh: BPD." />
                <x-bidang nama="ketua" label="Nama ketua (boleh dikosongkan)" maxlength="100" :value="old('ketua', $lembaga->ketua)" />
            </div>
            @include('admin.profil.partials.area', ['nama' => 'keterangan', 'label' => 'Tugas singkat (boleh dikosongkan)', 'nilai' => old('keterangan', $lembaga->keterangan), 'baris' => 3, 'maks' => 300, 'bantuan' => 'Satu atau dua kalimat tentang peran lembaga ini.'])
            <x-bidang nama="urutan" label="Urutan tampil" type="number" min="0" max="999" inputmode="numeric" :value="old('urutan', $lembaga->urutan ?? 0)" bantuan="Angka kecil tampil lebih dulu." class="max-w-xs" />

            <x-tombol>{{ $lembaga->exists ? 'Simpan perubahan' : 'Tambahkan' }}</x-tombol>
        </form>
    </x-kartu>
@endsection
