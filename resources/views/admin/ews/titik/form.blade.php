@extends('layouts.admin')
@section('judul', $titik->exists ? 'Ubah titik' : 'Tambah titik')
@push('skrip')
    @vite('resources/js/ews/admin-wilayah.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$titik->exists ? 'Ubah titik: '.$titik->nama : 'Tambah titik di peta'" keterangan="Titik ini hanya tampil di peta warga. Tidak mengirim data sensor dan tidak mengubah status Aman, Waspada, atau Awas." />

    <form method="POST" action="{{ $titik->exists ? route('admin.ews.titik.update', $titik) : route('admin.ews.titik.store') }}" class="space-y-4">
        @csrf
        @if ($titik->exists)
            @method('PUT')
        @endif

        <x-bidang nama="nama" label="Nama titik" required maxlength="100" :value="old('nama', $titik->nama)" />

        <fieldset>
            <legend class="mb-1 font-bold">Jenis titik</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach (\App\Models\Ews\Titik::JENIS as $kunci => $jenis)
                    <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border border-garis-halus p-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                        <input type="radio" name="jenis" value="{{ $kunci }}" class="mt-1 size-5 shrink-0" @checked(old('jenis', $titik->jenis) === $kunci) required>
                        <span><span class="block font-bold">{{ $jenis['nama'] }}</span><span class="block text-sm text-redup">{{ $jenis['keterangan'] }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('jenis')
                <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
            @enderror
        </fieldset>

        @include('admin.ews.partials.lokasi-titik', [
            'judul' => 'Koordinat titik',
            'bantuan' => 'Wajib diisi.',
            'wajib' => true,
            'lintang' => $titik->lintang,
            'bujur' => $titik->bujur,
        ])

        <div>
            <div id="peta-wilayah" class="relative z-0 h-96 rounded-lg border border-garis-halus bg-kosong-lembut" data-lainnya='@json($lainnya)' role="region" aria-label="Peta untuk menaruh titik. Koordinat juga bisa diisi manual di atas."></div>
        </div>

        <details @if ($errors->has('poligon') || $titik->poligon) open @endif>
            <summary class="inline-flex min-h-11 items-center font-bold underline">Batas area (opsional, untuk EWS alarm)</summary>
            <div class="mb-2 mt-2 flex flex-wrap gap-2">
                <x-tombol type="button" varian="kedua" id="mulai-gambar">Mulai menggambar</x-tombol>
                <x-tombol type="button" varian="kedua" id="urungkan" hidden>Urungkan titik</x-tombol>
                <x-tombol type="button" varian="kedua" id="selesai-gambar" hidden>Selesai</x-tombol>
            </div>
            <p id="status-gambar" class="mb-2 text-sm font-bold" role="status"></p>
            <label for="poligon" class="block text-sm">Satu titik per baris dalam bentuk "lintang, bujur" (minimal 3 baris), atau gambar di peta. Kosongkan bila tidak perlu.</label>
            <textarea id="poligon" name="poligon" rows="5" class="isian mt-1 font-mono text-sm" @error('poligon') aria-invalid="true" aria-describedby="poligon-galat" @enderror>{{ old('poligon', $titik->poligon ? json_encode($titik->poligon) : '') }}</textarea>
            @error('poligon')
                <p id="poligon-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
            @enderror
        </details>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan titik</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.ews.wilayah.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
