@extends('layouts.admin')
@section('judul', $langkah->exists ? 'Ubah langkah' : 'Tambah langkah')
@push('skrip')
    @vite('resources/js/edukasi/admin.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$langkah->exists ? 'Ubah langkah' : 'Tambah langkah'" :keterangan="'Materi: '.$materi->judul" />

    <x-kartu>
        <form method="POST" action="{{ $langkah->exists ? route('admin.edukasi.langkah.update', $langkah) : route('admin.edukasi.langkah.store', $materi) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($langkah->exists)
                @method('PUT')
            @endif

            <x-bidang nama="judul" label="Judul langkah" required maxlength="150" :value="old('judul', $langkah->judul)" />
            @include('admin.edukasi.partials.area', ['nama' => 'isi', 'label' => 'Isi langkah', 'nilai' => old('isi', $langkah->isi), 'wajib' => true])
            @include('admin.edukasi.partials.gambar', ['url' => $langkah->urlGambar()])

            <div class="flex flex-wrap gap-2">
                <x-tombol>Simpan langkah</x-tombol>
                <x-tombol varian="kedua" :href="route('admin.edukasi.materi.edit', $materi).'#langkah'">Batal</x-tombol>
            </div>
        </form>
    </x-kartu>
@endsection
