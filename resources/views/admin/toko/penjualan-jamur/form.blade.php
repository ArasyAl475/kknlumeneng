@extends('layouts.admin')
@section('judul', $penjualan->exists ? 'Ubah penjualan jamur' : 'Catat penjualan jamur')

@section('isi')
    <x-judul-halaman :judul="$penjualan->exists ? 'Ubah penjualan jamur' : 'Catat penjualan jamur'" />

    <form method="POST" action="{{ $penjualan->exists ? route('admin.toko.penjualan-jamur.update', $penjualan) : route('admin.toko.penjualan-jamur.store') }}" class="max-w-2xl space-y-4 rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-6">
        @csrf
        @if ($penjualan->exists)
            @method('PUT')
        @endif

        <x-bidang nama="tanggal_penjualan" label="Tanggal penjualan" type="date" required :max="now()->format('Y-m-d')" :value="old('tanggal_penjualan', ($penjualan->tanggal_penjualan ?? now())->format('Y-m-d'))" />
        @php $cangkang = (string) old('dengan_cangkang_kopi', $penjualan->dengan_cangkang_kopi === null ? '' : (int) $penjualan->dengan_cangkang_kopi); @endphp
        <div>
            <label for="dengan_cangkang_kopi" class="mb-1 block font-bold">Jamur dari baglog</label>
            <select id="dengan_cangkang_kopi" name="dengan_cangkang_kopi" class="isian" required @error('dengan_cangkang_kopi') aria-invalid="true" aria-describedby="dengan_cangkang_kopi-galat" @enderror>
                <option value="">Pilih media tanam</option>
                <option value="1" @selected($cangkang === '1')>Dengan cangkang kopi</option>
                <option value="0" @selected($cangkang === '0')>Tanpa cangkang kopi</option>
            </select>
            <p class="mt-1 text-sm text-redup">Kalau yang terjual campuran, catat sebagai dua penjualan.</p>
            @error('dengan_cangkang_kopi')
                <p id="dengan_cangkang_kopi-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-bidang nama="jumlah_kg" label="Jumlah terjual (kg)" type="number" step="0.01" min="0.01" required inputmode="decimal" :value="old('jumlah_kg', $penjualan->jumlah_kg)" />
            <x-bidang nama="total_rupiah" label="Uang diterima (Rp)" type="number" step="1" min="0" required inputmode="numeric" bantuan="Tulis angka saja tanpa titik, contoh: 150000." :value="old('total_rupiah', $penjualan->total_rupiah)" />
        </div>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.toko.penjualan-jamur.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
