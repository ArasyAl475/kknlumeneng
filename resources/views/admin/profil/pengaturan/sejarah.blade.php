@extends('admin.profil.pengaturan.kerangka', ['ruteLihat' => 'profil.visi-misi'])
@push('skrip')
    @vite('resources/js/profil/editor.js')
@endpush

@section('isian')
    <x-kartu judul="Visi & misi">
        <div class="space-y-4">
            @include('admin.profil.partials.area', ['nama' => 'visi', 'label' => 'Visi', 'nilai' => old('visi', $profil->nilai('visi')), 'baris' => 3, 'maks' => 500, 'bantuan' => 'Satu atau dua kalimat cita-cita desa.'])
            @include('admin.profil.partials.area', ['nama' => 'misi', 'label' => 'Misi', 'nilai' => old('misi', $profil->nilai('misi')), 'baris' => 8, 'maks' => 3000, 'bantuan' => 'Tulis satu misi per baris (tekan Enter). Nomor dibuat otomatis, jadi tidak perlu menulis 1, 2, 3.'])
        </div>
    </x-kartu>

    <x-kartu judul="Sejarah desa">
        @include('admin.profil.partials.editor', ['nama' => 'sejarah', 'label' => 'Cerita sejarah dan asal-usul nama desa', 'nilai' => old('sejarah', \App\Services\Profil\IsiKaya::keHtml($profil->nilai('sejarah'))), 'bantuan' => 'Dikosongkan = bagian Sejarah tidak tampil.'])
    </x-kartu>
@endsection
