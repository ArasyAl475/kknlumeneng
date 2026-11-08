@extends('layouts.admin')
@section('judul', $panen->exists ? 'Ubah panen jamur' : 'Catat panen jamur')

@section('isi')
    <x-judul-halaman :judul="$panen->exists ? 'Ubah panen jamur' : 'Catat panen jamur'" />

    <form method="POST" action="{{ $panen->exists ? route('admin.toko.panen-jamur.update', $panen) : route('admin.toko.panen-jamur.store') }}" class="max-w-2xl space-y-4 rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-6">
        @csrf
        @if ($panen->exists)
            @method('PUT')
        @endif

        <x-bidang nama="tanggal_panen" label="Tanggal panen" type="date" required :max="now()->format('Y-m-d')" :value="old('tanggal_panen', ($panen->tanggal_panen ?? now())->format('Y-m-d'))" />
        <x-bidang nama="jumlah_kg" label="Jumlah panen (kg)" type="number" step="0.01" min="0.01" required inputmode="decimal" :value="old('jumlah_kg', $panen->jumlah_kg)" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-bidang nama="jumlah_baglog" label="Jumlah baglog" type="number" step="1" min="1" required inputmode="numeric" bantuan="Banyak kantong media tanam untuk panen ini." :value="old('jumlah_baglog', $panen->jumlah_baglog)" />
            <x-bidang nama="biaya_baglog_rupiah" label="Biaya membuat baglog (Rp)" type="number" step="1" min="0" inputmode="numeric" bantuan="Boleh kosong. Total biaya semua baglog, tidak tampil ke warga." :value="old('biaya_baglog_rupiah', $panen->biaya_baglog_rupiah)" />
        </div>

        {{-- Tanpa pilihan awal: nilai bawaan yang lupa diganti akan diam-diam merusak total cangkang kopi. --}}
        @php $cangkang = (string) old('dengan_cangkang_kopi', $panen->exists ? (int) $panen->dengan_cangkang_kopi : ''); @endphp
        <div>
            <label for="dengan_cangkang_kopi" class="mb-1 block font-bold">Media tanam</label>
            <select id="dengan_cangkang_kopi" name="dengan_cangkang_kopi" class="isian" required @error('dengan_cangkang_kopi') aria-invalid="true" aria-describedby="dengan_cangkang_kopi-galat" @enderror>
                <option value="">Pilih media tanam</option>
                <option value="1" @selected($cangkang === '1')>Dengan cangkang kopi</option>
                <option value="0" @selected($cangkang === '0')>Tanpa cangkang kopi</option>
            </select>
            <p class="mt-1 text-sm text-redup">Kalau hasil panen berasal dari dua media, catat sebagai dua panen.</p>
            @error('dengan_cangkang_kopi')
                <p id="dengan_cangkang_kopi-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.toko.panen-jamur.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
