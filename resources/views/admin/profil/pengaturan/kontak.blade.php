@extends('admin.profil.pengaturan.kerangka')

@section('isian')
    <x-kartu judul="Kantor desa">
        <div class="space-y-4">
            @include('admin.profil.partials.area', ['nama' => 'alamat_kantor', 'label' => 'Alamat kantor desa', 'nilai' => old('alamat_kantor', $profil->nilai('alamat_kantor')), 'baris' => 2, 'maks' => 255])
            <div class="grid gap-4 sm:grid-cols-2">
                <x-bidang nama="telepon" label="Nomor telepon / WhatsApp kantor" type="tel" maxlength="30" :value="old('telepon', $profil->nilai('telepon'))"
                    bantuan="Warga bisa langsung menelepon dengan sekali ketuk." />
                <x-bidang nama="email" label="Alamat surel (email)" type="email" maxlength="100" :value="old('email', $profil->nilai('email'))" />
            </div>
            @include('admin.profil.partials.area', ['nama' => 'jam_pelayanan', 'label' => 'Jam pelayanan', 'nilai' => old('jam_pelayanan', $profil->nilai('jam_pelayanan')), 'baris' => 3, 'maks' => 500, 'bantuan' => 'Satu baris untuk satu jadwal. Contoh: Senin–Kamis: 08.00–15.00 WIB'])
            <x-bidang nama="tautan_peta" label="Tautan Google Maps (boleh dikosongkan)" type="url" maxlength="500" :value="old('tautan_peta', $profil->nilai('tautan_peta'))"
                bantuan="Buka Google Maps, cari kantor desa, tekan Bagikan, lalu Salin link dan tempel di sini." />
        </div>
    </x-kartu>
@endsection
