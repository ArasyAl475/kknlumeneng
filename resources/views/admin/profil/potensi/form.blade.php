@extends('layouts.admin')
@section('judul', $potensi->exists ? 'Ubah potensi' : 'Tambah potensi')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$potensi->exists ? 'Ubah: '.$potensi->nama : 'Tambah potensi desa'">
        <x-tombol varian="kedua" :href="route('admin.profil.potensi.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        <form method="POST" action="{{ $potensi->exists ? route('admin.profil.potensi.update', $potensi) : route('admin.profil.potensi.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($potensi->exists)
                @method('PUT')
            @endif

            <x-bidang nama="nama" label="Nama daerah atau potensi" required maxlength="120" :value="old('nama', $potensi->nama)" bantuan="Contoh: Kebun kopi Dusun Sikembang, Sawah terasering." />

            <fieldset>
                <legend class="mb-1 font-bold">Jenis potensi</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Models\Profil\Potensi::KATEGORI as $kunci => $k)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border-2 border-garis-halus bg-permukaan px-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                            <input type="radio" name="kategori" value="{{ $kunci }}" class="size-5" required @checked(old('kategori', $potensi->kategori) === $kunci)>
                            <x-ikon :nama="$k['ikon']" class="size-5 text-kopi" />
                            <span class="font-bold">{{ $k['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('kategori')
                    <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-bidang nama="hasil" label="Penghasil apa (boleh dikosongkan)" maxlength="1000" :value="old('hasil', $potensi->hasil)" bantuan="Pisahkan dengan koma. Contoh: Kopi, durian, manggis. Di situs tampil sebagai label-label kecil." />
            <x-bidang nama="lokasi" label="Lokasi: di mana" maxlength="255" :value="old('lokasi', $potensi->lokasi)" bantuan="Sebut dusun atau dukuh. Contoh: Dusun VI Sikembang." />
            <x-bidang nama="luas" label="Luas atau jumlah (boleh dikosongkan)" maxlength="40" :value="old('luas', $potensi->luas)" bantuan="Contoh: 153,33 ha, atau 14 mata air." class="max-w-xs" />

            <div>
                <label for="keterangan" class="mb-1 block font-bold">Penjelasan singkat</label>
                <textarea id="keterangan" name="keterangan" rows="5" maxlength="2000" class="isian" @error('keterangan') aria-invalid="true" @enderror>{{ old('keterangan', $potensi->keterangan) }}</textarea>
                <p class="mt-1 text-sm text-redup">Dua sampai empat kalimat sudah cukup.</p>
                @error('keterangan')
                    <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                @enderror
            </div>

            <x-bidang nama="urutan" label="Urutan tampil" type="number" min="0" max="999" inputmode="numeric" :value="old('urutan', $potensi->urutan ?? 0)"
                bantuan="Angka kecil tampil lebih dulu." class="max-w-xs" />

            {{-- Foto-foto potensi. Disimpan di galeri, jadi otomatis tampil juga di menu Galeri. --}}
            <fieldset class="space-y-3">
                <legend class="font-bold">Foto-foto potensi</legend>
                <p class="text-sm text-redup">Di situs, foto-foto ini ditata seperti dinding galeri: yang pertama paling besar, lainnya mengisi di sekitarnya. Semua foto otomatis masuk ke menu Galeri.</p>

                @if ($potensi->exists && $potensi->fotos->isNotEmpty())
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($potensi->fotos as $foto)
                            <li class="rounded-xl border border-garis-halus bg-permukaan p-2">
                                <img src="{{ $foto->urlGambar() }}" alt="Foto {{ $loop->iteration }} {{ $potensi->nama }}" loading="lazy" class="aspect-[4/3] w-full rounded-lg object-cover">
                                <label class="mt-2 flex min-h-11 items-center gap-2 text-sm">
                                    <input type="radio" name="utama" value="{{ $foto->id }}" class="size-5" @checked((string) old('utama', $loop->first ? $foto->id : '') === (string) $foto->id)>
                                    <span>Jadikan foto utama (terbesar)</span>
                                </label>
                                <label class="flex min-h-11 items-center gap-2 text-sm font-bold text-awas">
                                    <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}" class="size-5" @checked(in_array((string) $foto->id, array_map('strval', (array) old('hapus_foto', [])), true))>
                                    <span>Hapus foto ini</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @include('admin.profil.partials.foto-banyak', [
                    'nama' => 'fotos',
                    'label' => $potensi->exists ? 'Tambah foto (boleh dikosongkan)' : 'Foto (boleh dikosongkan)',
                    'bantuan' => 'Foto mendatar dan tegak sama-sama cocok. Paling banyak '.\App\Http\Requests\Profil\PotensiRequest::MAKS_FOTO_TOTAL.' foto per potensi.',
                    'maks' => \App\Http\Requests\Profil\PotensiRequest::MAKS_FOTO_SEKALIGUS,
                ])
            </fieldset>

            <x-tombol>{{ $potensi->exists ? 'Simpan perubahan' : 'Tambahkan' }}</x-tombol>
        </form>
    </x-kartu>
@endsection
