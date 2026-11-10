@extends('layouts.admin')
@section('judul', 'Ubah foto galeri')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Ubah foto galeri" :keterangan="$foto->judulTampil()">
        <x-tombol varian="kedua" :href="route('admin.profil.galeri.index')">Kembali ke galeri</x-tombol>
    </x-judul-halaman>

    @if ($foto->terhubung())
        {{-- Hanya keterangan: sambungan ke berita dibuat otomatis dan tidak diubah dari sini. --}}
        <div role="note" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border-2 border-kopi/30 bg-permukaan p-4">
            <x-ikon nama="berita" class="size-5 shrink-0 text-kopi" />
            <p class="min-w-0 flex-1 basis-56">Foto ini dokumentasi dari <span class="font-bold">{{ $foto->berita?->judul }}</span>. Judul atau keterangan yang dikosongkan memakai isi berita.</p>
            <x-tombol varian="kedua" :href="route('admin.profil.berita.edit', $foto->berita_id)">Buka beritanya</x-tombol>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        <x-kartu>
            <img src="{{ $foto->urlGambar() }}" alt="Foto yang sedang diubah" class="w-full rounded-xl border border-garis-halus">
            @if (! $foto->terhubung() || $foto->berita?->sudahTerbit())
                <a href="{{ route('galeri.show', $foto) }}" target="_blank" rel="noopener" class="tautan mt-3 inline-flex min-h-11 items-center">Lihat di situs</a>
            @endif
        </x-kartu>

        <x-kartu>
            <form method="POST" action="{{ route('admin.profil.galeri.update', $foto) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                <x-bidang nama="judul" label="Judul (boleh dikosongkan)" maxlength="150" :value="old('judul', $foto->judul)" />
                @include('admin.profil.partials.area', ['nama' => 'keterangan', 'label' => 'Keterangan (boleh dikosongkan)', 'nilai' => old('keterangan', $foto->keterangan), 'baris' => 3, 'maks' => 500])
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-bidang nama="tanggal" label="Tanggal foto" type="date" :max="now()->toDateString()" :value="old('tanggal', $foto->tanggal?->toDateString())" />
                    <x-bidang nama="urutan" label="Urutan tampil" type="number" min="0" max="999" inputmode="numeric" :value="old('urutan', $foto->urutan)" bantuan="Angka kecil tampil lebih dulu." />
                </div>
                @include('admin.profil.partials.foto', ['nama' => 'foto', 'label' => 'Ganti foto (boleh dikosongkan)', 'url' => null])
                <x-tombol>Simpan perubahan</x-tombol>
            </form>
        </x-kartu>
    </div>
@endsection
