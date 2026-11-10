@extends('admin.profil.pengaturan.kerangka')
@push('skrip')
    @vite('resources/js/profil/editor.js')
@endpush

@section('isian')
    <x-kartu judul="Identitas desa">
        <div class="space-y-4">
            <x-bidang nama="nama_desa" label="Nama desa" required maxlength="60" :value="old('nama_desa', $profil->nilai('nama_desa'))"
                bantuan="Tanpa kata “Desa”. Contoh: Lumeneng." />
            <x-bidang nama="tagline" label="Semboyan singkat (boleh dikosongkan)" maxlength="150" :value="old('tagline', $profil->nilai('tagline'))"
                bantuan="Tampil di bawah nama desa. Contoh: Desa tangguh bencana di lereng Paninggaran." />
            <div class="grid gap-4 sm:grid-cols-3">
                <x-bidang nama="kecamatan" label="Kecamatan" maxlength="60" :value="old('kecamatan', $profil->nilai('kecamatan'))" />
                <x-bidang nama="kabupaten" label="Kabupaten" maxlength="60" :value="old('kabupaten', $profil->nilai('kabupaten'))" />
                <x-bidang nama="provinsi" label="Provinsi" maxlength="60" :value="old('provinsi', $profil->nilai('provinsi'))" />
            </div>
            {{-- Foto pindah ke Desain Web agar semua gambar tata letak diatur dari satu tempat. --}}
            @include('admin.desain.partials.arahan', ['teks' => 'Foto sampul dan foto kepala desa sekarang diganti lewat menu Desain Web.'])
        </div>
    </x-kartu>

    <x-kartu judul="Sambutan kepala desa">
        <div class="space-y-4">
            <x-bidang nama="nama_kepala_desa" label="Nama kepala desa" maxlength="100" :value="old('nama_kepala_desa', $profil->nilai('nama_kepala_desa'))" />
            @include('admin.profil.partials.editor', ['nama' => 'sambutan', 'label' => 'Isi sambutan', 'nilai' => old('sambutan', \App\Services\Profil\IsiKaya::keHtml($profil->nilai('sambutan')))])
        </div>
    </x-kartu>
@endsection
