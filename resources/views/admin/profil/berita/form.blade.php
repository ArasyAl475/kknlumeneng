@extends('layouts.admin')
@section('judul', $berita->exists ? 'Ubah tulisan' : 'Tulis baru')
@push('skrip')
    @vite(['resources/js/profil/admin.js', 'resources/js/profil/editor.js'])
@endpush

@php $status = old('status', $berita->exists && ! $berita->sudahTerbit() ? 'draf' : 'terbit'); @endphp

@section('isi')
    <x-judul-halaman :judul="$berita->exists ? 'Ubah tulisan' : 'Tulis berita atau artikel'" :keterangan="$berita->exists ? $berita->judul : 'Tulis seperti bercerita kepada tetangga: singkat, jelas, dan sebutkan kapan serta di mana.'">
        @if ($berita->exists)
            <x-tombol varian="kedua" :href="route('admin.profil.berita.pratinjau', $berita)">Pratinjau</x-tombol>
            @if ($berita->sudahTerbit())
                <x-tombol varian="kedua" :href="route('berita.show', $berita->slug)" target="_blank" rel="noopener">Lihat di situs</x-tombol>
            @endif
        @endif
        <x-tombol varian="kedua" :href="route('admin.profil.berita.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    <form method="POST" action="{{ $berita->exists ? route('admin.profil.berita.update', $berita) : route('admin.profil.berita.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if ($berita->exists)
            @method('PUT')
        @endif

        <x-kartu>
            <div class="space-y-4">
                <fieldset>
                    <legend class="mb-1 font-bold">Jenis tulisan</legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ([
                            'berita' => 'Kabar kegiatan yang sudah terjadi.',
                            'pengumuman' => 'Informasi penting untuk warga.',
                            'artikel' => 'Bacaan, tips, atau pengetahuan.',
                        ] as $kunci => $keterangan)
                            <label class="flex min-h-12 items-start gap-3 rounded-xl border-2 border-garis-halus bg-permukaan p-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                                <input type="radio" name="jenis" value="{{ $kunci }}" class="mt-0.5 size-5" required @checked(old('jenis', $berita->jenis) === $kunci)>
                                <span><span class="block font-bold">{{ \App\Models\Profil\Berita::JENIS[$kunci] }}</span><span class="block text-sm text-redup">{{ $keterangan }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('jenis')
                        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-bidang nama="judul" label="Judul" required maxlength="150" :value="old('judul', $berita->judul)" bantuan="Contoh: Kerja bakti membersihkan saluran air di Dusun Krajan." />
                @include('admin.profil.partials.area', ['nama' => 'ringkasan', 'label' => 'Ringkasan', 'nilai' => old('ringkasan', $berita->ringkasan), 'baris' => 2, 'maks' => 300, 'wajib' => true, 'bantuan' => 'Satu atau dua kalimat yang tampil di daftar berita.'])
                @include('admin.profil.partials.editor', ['nama' => 'isi', 'label' => 'Isi tulisan', 'nilai' => old('isi', \App\Services\Profil\IsiKaya::keHtml($berita->isi)), 'wajib' => true])
                <x-bidang nama="penulis" label="Nama penulis (boleh dikosongkan)" maxlength="100" :value="old('penulis', $berita->penulis)" />
            </div>
        </x-kartu>

        <x-kartu judul="Foto">
            <div class="space-y-5">
                @include('admin.profil.partials.foto', ['nama' => 'gambar', 'label' => 'Foto utama / sampul (boleh dikosongkan)', 'url' => $berita->urlGambar(), 'bantuan' => 'Tampil di kartu berita dan paling atas halaman. Foto mendatar lebih bagus.'])
                @include('admin.profil.partials.foto-banyak', ['nama' => 'foto_kegiatan', 'label' => $berita->exists ? 'Tambah foto dokumentasi kegiatan (boleh dikosongkan)' : 'Foto dokumentasi kegiatan (boleh dikosongkan)', 'bantuan' => 'Tampil di bawah tulisan dan otomatis masuk menu Galeri.'])
            </div>
        </x-kartu>

        <x-kartu judul="Kapan warga bisa membaca?">
            <fieldset>
                <legend class="sr-only">Status tulisan</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex min-h-12 items-start gap-3 rounded-xl border-2 border-garis-halus bg-permukaan p-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                        <input type="radio" name="status" value="terbit" class="mt-0.5 size-5" @checked($status === 'terbit')>
                        <span><span class="block font-bold">Terbitkan sekarang</span><span class="block text-sm text-redup">Langsung tampil di halaman Berita.</span></span>
                    </label>
                    <label class="flex min-h-12 items-start gap-3 rounded-xl border-2 border-garis-halus bg-permukaan p-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                        <input type="radio" name="status" value="draf" class="mt-0.5 size-5" @checked($status === 'draf')>
                        <span><span class="block font-bold">Simpan sebagai draf</span><span class="block text-sm text-redup">Belum terlihat warga, bisa dilanjutkan nanti.</span></span>
                    </label>
                </div>
                @error('status')
                    <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                @enderror
            </fieldset>
            <div class="mt-4">
                <x-tombol>Simpan tulisan</x-tombol>
            </div>
        </x-kartu>
    </form>

    {{-- Di luar formulir utama, karena formulir hapus tidak boleh bersarang di dalam formulir lain. --}}
    @if ($berita->exists)
        <x-kartu class="mt-4" id="foto-kegiatan">
            <div class="mb-3">
                <h2 class="text-lg font-bold">Foto dokumentasi ({{ $berita->foto->count() }})</h2>
                <p class="text-sm text-redup">Foto-foto ini otomatis tampil di menu Galeri. Tambah foto lewat bagian <span class="font-bold">Foto</span> di atas.</p>
            </div>
            @if ($berita->foto->isEmpty())
                <p class="text-redup">Belum ada foto dokumentasi. Pilih foto di bagian <span class="font-bold">Foto</span> di atas, lalu simpan.</p>
            @else
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($berita->foto as $f)
                        <li class="overflow-hidden rounded-xl border border-garis-halus bg-permukaan">
                            <img src="{{ $f->urlGambar() }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                            <div class="p-2">
                                <p class="truncate text-sm font-bold">{{ $f->judul ?: 'Tanpa judul' }}</p>
                                <div class="mt-2 flex gap-2">
                                    <x-tombol varian="kedua" :href="route('admin.profil.galeri.edit', $f)" class="flex-1 px-2" aria-label="Ubah foto {{ $loop->iteration }}">Ubah</x-tombol>
                                    <form method="POST" action="{{ route('admin.profil.galeri.destroy', $f) }}" data-konfirmasi="Hapus foto ini dari kegiatan dan galeri?" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <x-tombol varian="kedua" class="w-full px-2" aria-label="Hapus foto {{ $loop->iteration }}">Hapus</x-tombol>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>
    @endif
@endsection
