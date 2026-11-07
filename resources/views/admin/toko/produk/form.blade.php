@extends('layouts.admin')
@section('judul', $produk->exists ? 'Ubah produk' : 'Tambah produk')
@push('skrip')
    @vite('resources/js/toko/admin.js')
@endpush

@use('App\Services\Toko\DaftarMarketplace')
@php
    $urlKembali = $kembali ? route('admin.toko.marketplace.show', $kembali) : route('admin.toko.produk.index');

    // Selalu sisakan baris kosong agar admin bisa menambah tanpa JavaScript.
    $isiBaris = function (array $baris, array $kosong): array {
        $baris = array_values($baris);
        $target = max(3, count($baris) + 1);

        return array_pad($baris, $target, $kosong);
    };
    $barisVarian = $isiBaris(
        old('varian', $produk->varian->map(fn ($v) => ['nama' => $v->nama, 'harga' => $v->harga, 'tersedia' => $v->tersedia ? '1' : '0'])->all()),
        ['nama' => '', 'harga' => '', 'tersedia' => '1'],
    );
    $barisSpesifikasi = $isiBaris(old('spesifikasi', $produk->spesifikasi ?? []), ['label' => '', 'nilai' => '']);
@endphp

@section('isi')
    <a href="{{ $urlKembali }}" class="tautan inline-flex min-h-11 items-center gap-1">
        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Kembali
    </a>
    <x-judul-halaman :judul="$produk->exists ? 'Ubah: '.$produk->nama : 'Tambah produk'"
        keterangan="Isi sekali untuk ketiga marketplace. Bagian yang belum ada datanya boleh dikosongkan dan tidak akan tampil di situs." />

    <form method="POST" action="{{ $produk->exists ? route('admin.toko.produk.update', $produk) : route('admin.toko.produk.store') }}" class="space-y-4">
        @csrf
        @if ($produk->exists)
            @method('PUT')
        @endif
        @if ($kembali)
            <input type="hidden" name="kembali" value="{{ $kembali }}">
        @endif

        <p class="text-redup"><span class="font-bold text-awas" aria-hidden="true">*</span> Isian bertanda <strong>(wajib)</strong> harus diisi sebelum produk bisa disimpan.</p>

        <x-kartu judul="Data produk">
            <div class="space-y-4">
                <div>
                    <label for="nama" class="mb-1 block font-bold">Nama produk @include('admin.toko.produk.penanda-wajib')</label>
                    <input id="nama" name="nama" type="text" required aria-required="true" maxlength="120" class="isian"
                        value="{{ old('nama', $produk->nama) }}" @error('nama') aria-invalid="true" aria-describedby="nama-galat" @enderror>
                    @error('nama')
                        <p id="nama-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="deskripsi" class="mb-1 block font-bold">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="5000" class="isian" @error('deskripsi') aria-invalid="true" aria-describedby="deskripsi-galat" @enderror>{{ old('deskripsi', $produk->deskripsi) }}</textarea>
                    <p class="mt-1 text-sm text-redup">Ceritakan asal, cara membuat, atau cara memakai. Baris baru akan tetap tampil sebagai baris baru.</p>
                    @error('deskripsi')
                        <p id="deskripsi-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="harga" class="mb-1 block font-bold">Harga (Rp) @include('admin.toko.produk.penanda-wajib')</label>
                    <input id="harga" name="harga" type="text" inputmode="numeric" required aria-required="true" maxlength="15" class="isian"
                        placeholder="contoh: 25000" value="{{ old('harga', $produk->harga) }}"
                        aria-describedby="harga-bantuan @error('harga') harga-galat @enderror" @error('harga') aria-invalid="true" @enderror>
                    <p id="harga-bantuan" class="mt-1 text-sm text-redup">Tulis angkanya saja. Kalau varian di bawah diberi harga, harga varian yang ditampilkan; harga ini tetap jadi harga dasar.</p>
                    @error('harga')
                        <p id="harga-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                    @enderror
                </div>

                <x-bidang nama="urutan" label="Urutan tampil" type="number" min="0" max="999" class="max-w-48"
                    :value="old('urutan', $produk->urutan ?? 0)" bantuan="Angka kecil tampil lebih dulu." />

                <div class="space-y-1">
                    <input type="hidden" name="tersedia" value="0">
                    <label for="tersedia" class="flex min-h-11 items-center gap-3 font-bold">
                        <input type="checkbox" id="tersedia" name="tersedia" value="1" class="size-6" @checked(old('tersedia', $produk->tersedia ?? true))>
                        Stok tersedia
                    </label>
                    <input type="hidden" name="tampil" value="0">
                    <label for="tampil" class="flex min-h-11 items-center gap-3 font-bold">
                        <input type="checkbox" id="tampil" name="tampil" value="1" class="size-6" @checked(old('tampil', $produk->tampil ?? true))>
                        Tampilkan di situs
                    </label>
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Tautan di marketplace">
            <p class="mb-3 text-redup">Buka produk di aplikasi marketplace, tekan <strong>Bagikan</strong>, lalu <strong>Salin tautan</strong> dan tempel di sini. Kosongkan bila produk belum diunggah ke marketplace itu.</p>
            <div class="space-y-4">
                @foreach (DaftarMarketplace::KUNCI as $kunci)
                    <div>
                        <label for="tautan-{{ $kunci }}" class="mb-1 flex items-center gap-2 font-bold">
                            @include('toko.partials.logo', ['kunci' => $kunci])
                            {{ DaftarMarketplace::nama($kunci) }}
                        </label>
                        <input id="tautan-{{ $kunci }}" name="tautan[{{ $kunci }}]" inputmode="url" maxlength="500" class="isian"
                            placeholder="https://{{ DaftarMarketplace::domainUtama($kunci) }}/…" value="{{ old("tautan.{$kunci}", $produk->exists ? $produk->tautanUntuk($kunci) : '') }}"
                            @error("tautan.{$kunci}") aria-invalid="true" aria-describedby="tautan-{{ $kunci }}-galat" @enderror>
                        @error("tautan.{$kunci}")
                            <p id="tautan-{{ $kunci }}-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </x-kartu>

        <x-kartu judul="Varian (boleh kosong)">
            <p class="mb-3 text-redup">Misalnya ukuran 100 g dan 250 g, atau rasa original dan pedas. Baris yang namanya kosong diabaikan.</p>
            <div class="space-y-3" data-daftar-baris="varian">
                @foreach ($barisVarian as $i => $b)
                    @include('admin.toko.produk.baris-varian', ['i' => $i, 'b' => $b])
                @endforeach
            </div>
            <template data-cetakan="varian">@include('admin.toko.produk.baris-varian', ['i' => '__i__', 'b' => ['nama' => '', 'harga' => '', 'tersedia' => '1']])</template>
            <x-tombol varian="kedua" type="button" class="mt-3" data-tambah-baris="varian" hidden>Tambah baris varian</x-tombol>
        </x-kartu>

        <x-kartu judul="Spesifikasi (boleh kosong)">
            <p class="mb-3 text-redup">Misalnya Berat: 250 g, Masa simpan: 6 bulan, Izin: PIRT nomor sekian. Isi hanya yang sudah pasti.</p>
            <div class="space-y-3" data-daftar-baris="spesifikasi">
                @foreach ($barisSpesifikasi as $i => $b)
                    @include('admin.toko.produk.baris-spesifikasi', ['i' => $i, 'b' => $b])
                @endforeach
            </div>
            <template data-cetakan="spesifikasi">@include('admin.toko.produk.baris-spesifikasi', ['i' => '__i__', 'b' => ['label' => '', 'nilai' => '']])</template>
            <x-tombol varian="kedua" type="button" class="mt-3" data-tambah-baris="spesifikasi" hidden>Tambah baris spesifikasi</x-tombol>
        </x-kartu>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan produk</x-tombol>
            <x-tombol varian="kedua" :href="$urlKembali">Batal</x-tombol>
        </div>
    </form>

    @if ($produk->exists)
        <x-kartu class="mt-6" id="foto">
            <h2 class="text-lg font-bold">Foto produk ({{ $produk->foto->count() }} dari {{ \App\Models\Toko\Produk::MAKS_FOTO }})</h2>
            <p class="mt-1 text-redup">Foto pertama menjadi foto utama di daftar produk dan saat tautan dibagikan di WhatsApp. Foto diperkecil otomatis agar ringan dibuka.</p>

            @if ($produk->foto->isNotEmpty())
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($produk->foto as $i => $foto)
                        <li class="rounded-lg border border-garis-halus p-2">
                            <img src="{{ $foto->urlKecil() }}" alt="Foto {{ $i + 1 }} {{ $produk->nama }}" width="400" height="400" loading="lazy" class="aspect-square w-full rounded object-cover">
                            <p class="mt-1 text-sm font-bold">{{ $i === 0 ? 'Foto utama' : 'Foto '.($i + 1) }}</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if ($i > 0)
                                    <form method="POST" action="{{ route('admin.toko.foto.geser', [$produk, $foto, 'naik']) }}">
                                        @csrf
                                        <x-tombol varian="kedua" class="px-3" aria-label="Pindahkan foto {{ $i + 1 }} ke depan">←</x-tombol>
                                    </form>
                                @endif
                                @if (! $loop->last)
                                    <form method="POST" action="{{ route('admin.toko.foto.geser', [$produk, $foto, 'turun']) }}">
                                        @csrf
                                        <x-tombol varian="kedua" class="px-3" aria-label="Pindahkan foto {{ $i + 1 }} ke belakang">→</x-tombol>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.toko.foto.destroy', [$produk, $foto]) }}" data-konfirmasi="Hapus foto {{ $i + 1 }}?">
                                    @csrf
                                    @method('DELETE')
                                    <x-tombol varian="kedua" class="px-3" aria-label="Hapus foto {{ $i + 1 }}">Hapus</x-tombol>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($produk->foto->count() < \App\Models\Toko\Produk::MAKS_FOTO)
                <form method="POST" action="{{ route('admin.toko.foto.store', $produk) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="berkas-foto" class="mb-1 block font-bold">Tambah foto</label>
                        <input type="file" id="berkas-foto" name="foto[]" multiple accept="image/jpeg,image/png,image/webp" class="isian" required>
                        <p class="mt-1 text-sm text-redup">JPG, PNG, atau WebP, maksimal {{ $batasFoto }} per foto. Kalau gagal, coba unggah satu per satu.</p>
                    </div>
                    <x-tombol>Unggah foto</x-tombol>
                </form>
            @endif
        </x-kartu>
    @else
        <p class="mt-6 rounded-md bg-kosong-lembut p-3">Foto bisa ditambahkan setelah produk disimpan.</p>
    @endif
@endsection
