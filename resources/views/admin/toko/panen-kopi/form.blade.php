@extends('layouts.admin')
@section('judul', $panen->exists ? 'Ubah panen kopi' : 'Catat panen kopi')

@section('isi')
    <x-judul-halaman :judul="$panen->exists ? 'Ubah panen kopi' : 'Catat panen kopi'" />

    <form method="POST" action="{{ $panen->exists ? route('admin.toko.panen-kopi.update', $panen) : route('admin.toko.panen-kopi.store') }}" class="max-w-2xl space-y-4 rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-6">
        @csrf
        @if ($panen->exists)
            @method('PUT')
        @endif

        <x-bidang nama="tanggal_panen" label="Tanggal panen" type="date" required :max="now()->format('Y-m-d')" :value="old('tanggal_panen', ($panen->tanggal_panen ?? now())->format('Y-m-d'))" />
        <x-bidang nama="jenis_kopi" label="Jenis kopi" required maxlength="100" list="daftar-jenis" bantuan="Contoh: Arabika atau Robusta." :value="old('jenis_kopi', $panen->jenis_kopi)" />
        <x-bidang nama="tempat_panen" label="Tempat panen" required maxlength="150" list="daftar-tempat" :value="old('tempat_panen', $panen->tempat_panen)" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-bidang nama="jumlah_kg" label="Jumlah panen (kg)" type="number" step="0.01" min="0.01" required inputmode="decimal" :value="old('jumlah_kg', $panen->jumlah_kg)" />
            <x-bidang nama="limbah_kg" label="Jumlah limbah (kg)" type="number" step="0.01" min="0" required inputmode="decimal" :value="old('limbah_kg', $panen->limbah_kg)" />
        </div>

        <datalist id="daftar-jenis">
            @foreach ($pilihan['jenis'] as $jenis)
                <option value="{{ $jenis }}"></option>
            @endforeach
        </datalist>
        <datalist id="daftar-tempat">
            @foreach ($pilihan['tempat'] as $tempat)
                <option value="{{ $tempat }}"></option>
            @endforeach
        </datalist>

        <div class="flex flex-wrap gap-2">
            <x-tombol>Simpan</x-tombol>
            <x-tombol varian="kedua" :href="route('admin.toko.panen-kopi.index')">Batal</x-tombol>
        </div>
    </form>
@endsection
