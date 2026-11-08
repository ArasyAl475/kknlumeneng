@extends('layouts.admin')
@section('judul', 'Info penjual')

@section('isi')
    <x-judul-halaman judul="Info penjual" keterangan="Tampil di bagian bawah setiap halaman produk. Ubah setelah pengelola toko desa ditentukan." />

    <x-kartu>
        <form method="POST" action="{{ route('admin.toko.pengaturan.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-bidang nama="nama_penjual" label="Nama penjual" required maxlength="100" :value="old('nama_penjual', $pengaturan['nama_penjual'])"
                bantuan="Misalnya nama BUMDes, Karang Taruna, atau kelompok UMKM yang mengelola toko." />
            <div>
                <label for="keterangan_penjual" class="mb-1 block font-bold">Keterangan singkat</label>
                <textarea id="keterangan_penjual" name="keterangan_penjual" rows="2" maxlength="300" class="isian" @error('keterangan_penjual') aria-invalid="true" @enderror>{{ old('keterangan_penjual', $pengaturan['keterangan_penjual']) }}</textarea>
                @error('keterangan_penjual')
                    <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                @enderror
            </div>
            <x-bidang nama="lokasi_kirim" label="Dikirim dari" required maxlength="100" :value="old('lokasi_kirim', $pengaturan['lokasi_kirim'])" />
            <x-tombol>Simpan</x-tombol>
        </form>
    </x-kartu>
@endsection
