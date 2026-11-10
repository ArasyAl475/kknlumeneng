@extends('layouts.admin')
@section('judul', $materi->exists ? 'Ubah materi' : 'Tambah materi')
@push('skrip')
    @vite('resources/js/edukasi/admin.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$materi->exists ? 'Ubah materi: '.$materi->judul : 'Tambah materi'" :keterangan="$materi->exists ? null : 'Materi baru tersimpan sebagai draf. Langkah-langkah bisa ditambahkan setelah disimpan.'">
        <x-tombol varian="kedua" :href="route('admin.edukasi.materi.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    @if ($materi->exists)
        {{-- Formulir terpisah dari isian di bawah, karena formulir HTML tidak boleh bersarang. --}}
        <x-kartu class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="flex flex-wrap items-center gap-2"><span class="font-bold">Status:</span> @include('admin.edukasi.partials.status')</p>
                <div class="flex flex-wrap gap-2">
                    <x-tombol varian="kedua" :href="route('admin.edukasi.materi.pratinjau', $materi)">Pratinjau</x-tombol>
                    @if ($materi->sudahTerbit())
                        <form method="POST" action="{{ route('admin.edukasi.materi.tarik', $materi) }}">
                            @csrf
                            <x-tombol varian="kedua">Jadikan draf</x-tombol>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.edukasi.materi.terbitkan', $materi) }}">
                            @csrf
                            <x-tombol>Terbitkan</x-tombol>
                        </form>
                    @endif
                </div>
            </div>
        </x-kartu>
    @endif

    @if ($kategori->isEmpty())
        <x-kartu>
            <p>Belum ada kategori. <a href="{{ route('admin.edukasi.kategori.create') }}" class="tautan">Tambahkan kategori dulu</a> sebelum menulis materi.</p>
        </x-kartu>
    @else
        <x-kartu judul="Isi materi">
            <form method="POST" action="{{ $materi->exists ? route('admin.edukasi.materi.update', $materi) : route('admin.edukasi.materi.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if ($materi->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="kategori_id" class="mb-1 block font-bold">Kategori</label>
                    <select id="kategori_id" name="kategori_id" class="isian" required @error('kategori_id') aria-invalid="true" @enderror>
                        <option value="">Pilih kategori</option>
                        @foreach ($kategori as $k)
                            <option value="{{ $k->id }}" @selected((string) old('kategori_id', $materi->kategori_id) === (string) $k->id)>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id')
                        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                    @enderror
                </div>

                <x-bidang nama="urutan" type="number" min="1" max="999" inputmode="numeric" label="Nomor bagian dalam kategori (boleh dikosongkan)" :value="old('urutan', $materi->exists ? $materi->urutan : '')" bantuan="Bagian 1 dibaca paling awal. Dikosongkan, materi baru menjadi bagian terakhir. Warga melihat tombol Bagian Sebelumnya dan Selanjutnya sesuai nomor ini." />
                <x-bidang nama="judul" label="Judul" required maxlength="150" :value="old('judul', $materi->judul)" />
                @include('admin.edukasi.partials.area', ['nama' => 'ringkasan', 'label' => 'Ringkasan', 'nilai' => old('ringkasan', $materi->ringkasan), 'baris' => 2, 'maks' => 300, 'wajib' => true, 'bantuan' => 'Satu atau dua kalimat yang tampil di daftar materi.'])
                @include('admin.edukasi.partials.gambar', ['url' => $materi->urlGambar(), 'label' => 'Gambar utama (boleh dikosongkan)'])

                <x-bidang nama="video_youtube" label="Tautan video YouTube (boleh dikosongkan)" maxlength="200" :value="old('video_youtube', $materi->video_youtube ? 'https://www.youtube.com/watch?v='.$materi->video_youtube : '')" />

                <p class="text-sm text-redup">Penjelasan boleh dikosongkan kalau materi berupa langkah-langkah. Bagian yang kosong tidak tampil di halaman warga.</p>
                @include('admin.edukasi.partials.area', ['nama' => 'penjelasan', 'label' => 'Penjelasan', 'nilai' => old('penjelasan', $materi->penjelasan), 'baris' => 6, 'maks' => 10000])

                <x-tombol>Simpan materi</x-tombol>
            </form>
        </x-kartu>
    @endif

    @if ($materi->exists)
        <x-kartu class="mt-4" id="langkah">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold">Langkah-langkah</h2>
                <x-tombol varian="kedua" :href="route('admin.edukasi.langkah.create', $materi)">Tambah langkah</x-tombol>
            </div>

            @if ($materi->langkah->isEmpty())
                <p class="text-redup">Belum ada langkah. Lewati bagian ini kalau materinya tidak berupa panduan praktik.</p>
            @endif

            <ol class="divide-y divide-garis-halus">
                @foreach ($materi->langkah as $l)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                            @if ($l->urlGambar())
                                <img src="{{ $l->urlGambar() }}" alt="" loading="lazy" class="size-14 shrink-0 rounded object-cover">
                            @endif
                            <p class="min-w-0 font-bold">{{ $loop->iteration }}. {{ $l->judul }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.edukasi.langkah.pindah', $l) }}" class="flex gap-2">
                                @csrf
                                @unless ($loop->first)
                                    <x-tombol varian="kedua" name="arah" value="naik" aria-label="Naikkan {{ $l->judul }}">Naik</x-tombol>
                                @endunless
                                @unless ($loop->last)
                                    <x-tombol varian="kedua" name="arah" value="turun" aria-label="Turunkan {{ $l->judul }}">Turun</x-tombol>
                                @endunless
                            </form>
                            <x-tombol varian="kedua" :href="route('admin.edukasi.langkah.edit', $l)" aria-label="Ubah {{ $l->judul }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.edukasi.langkah.destroy', $l) }}" data-konfirmasi="Hapus langkah {{ $l->judul }}?">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" aria-label="Hapus {{ $l->judul }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-kartu>
    @endif
@endsection
