@extends('layouts.admin')
@section('judul', $perangkat->exists ? 'Ubah perangkat' : 'Tambah perangkat')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman :judul="$perangkat->exists ? 'Ubah: '.$perangkat->nama : 'Tambah perangkat desa'">
        <x-tombol varian="kedua" :href="route('admin.profil.perangkat.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        <form method="POST" action="{{ $perangkat->exists ? route('admin.profil.perangkat.update', $perangkat) : route('admin.profil.perangkat.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($perangkat->exists)
                @method('PUT')
            @endif

            <x-bidang nama="nama" label="Nama lengkap" required maxlength="100" :value="old('nama', $perangkat->nama)" bantuan="Boleh dengan gelar. Contoh: H. Sutrisno, S.Pd." />
            <x-bidang nama="jabatan" label="Jabatan" required maxlength="100" :value="old('jabatan', $perangkat->jabatan)" bantuan="Contoh: Kepala Urusan Keuangan, Kepala Dusun Krajan." />

            <fieldset>
                <legend class="mb-1 font-bold">Letak di bagan struktur</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Models\Profil\Perangkat::KELOMPOK as $kunci => $label)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border-2 border-garis-halus bg-permukaan px-3 has-[:checked]:border-kopi has-[:checked]:bg-kopi/5">
                            <input type="radio" name="kelompok" value="{{ $kunci }}" class="size-5" required @checked(old('kelompok', $perangkat->kelompok) === $kunci)>
                            <span class="font-bold">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('kelompok')
                    <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-bidang nama="urutan" label="Urutan tampil" type="number" min="0" max="999" inputmode="numeric" :value="old('urutan', $perangkat->urutan ?? 0)"
                bantuan="Angka kecil tampil lebih dulu dalam kelompok yang sama. Contoh: Kaur Keuangan 1, Kaur Umum 2." class="max-w-xs" />

            @include('admin.profil.partials.foto', ['nama' => 'foto', 'label' => 'Foto (boleh dikosongkan)', 'url' => $perangkat->urlFoto(), 'bantuan' => 'Foto potret (tegak, wajah di bagian atas) paling bagus. Dengan foto, kartu tampil tegak dengan nama dan jabatan di bawah foto. Tanpa foto, situs menampilkan huruf awal nama.'])

            <x-tombol>{{ $perangkat->exists ? 'Simpan perubahan' : 'Tambahkan' }}</x-tombol>
        </form>
    </x-kartu>
@endsection
