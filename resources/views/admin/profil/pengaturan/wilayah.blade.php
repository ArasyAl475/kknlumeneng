@extends('admin.profil.pengaturan.kerangka')

@section('isian')
    <x-kartu judul="Desa dalam angka">
        <p class="mb-4 text-sm text-redup">Boleh ditulis dengan titik ribuan (3.412) dan koma desimal (512,5). Kotak yang dikosongkan tidak tampil di situs.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-bidang nama="jumlah_penduduk" label="Jumlah penduduk (jiwa)" inputmode="numeric" maxlength="12" :value="old('jumlah_penduduk', $profil->nilai('jumlah_penduduk'))" />
            <x-bidang nama="jumlah_kk" label="Jumlah kepala keluarga (KK)" inputmode="numeric" maxlength="12" :value="old('jumlah_kk', $profil->nilai('jumlah_kk'))" />
            <x-bidang nama="luas_wilayah" label="Luas wilayah (hektare)" inputmode="decimal" maxlength="12" :value="old('luas_wilayah', str_replace('.', ',', $profil->nilai('luas_wilayah')))" />
            <x-bidang nama="jumlah_dusun" label="Jumlah dusun" inputmode="numeric" maxlength="6" :value="old('jumlah_dusun', $profil->nilai('jumlah_dusun'))" />
            <x-bidang nama="jumlah_rw" label="Jumlah RW" inputmode="numeric" maxlength="6" :value="old('jumlah_rw', $profil->nilai('jumlah_rw'))" />
            <x-bidang nama="jumlah_rt" label="Jumlah RT" inputmode="numeric" maxlength="6" :value="old('jumlah_rt', $profil->nilai('jumlah_rt'))" />
            <x-bidang nama="ketinggian" label="Ketinggian (meter di atas laut)" inputmode="numeric" maxlength="6" :value="old('ketinggian', $profil->nilai('ketinggian'))" />
        </div>
    </x-kartu>

    <x-kartu judul="Batas wilayah">
        <p class="mb-4 text-sm text-redup">Nama desa atau wilayah yang berbatasan langsung.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-bidang nama="batas_utara" label="Sebelah utara" maxlength="100" :value="old('batas_utara', $profil->nilai('batas_utara'))" />
            <x-bidang nama="batas_selatan" label="Sebelah selatan" maxlength="100" :value="old('batas_selatan', $profil->nilai('batas_selatan'))" />
            <x-bidang nama="batas_barat" label="Sebelah barat" maxlength="100" :value="old('batas_barat', $profil->nilai('batas_barat'))" />
            <x-bidang nama="batas_timur" label="Sebelah timur" maxlength="100" :value="old('batas_timur', $profil->nilai('batas_timur'))" />
        </div>
    </x-kartu>
@endsection
