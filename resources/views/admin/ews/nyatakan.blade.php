@extends('layouts.admin')
@section('judul', 'Nyatakan Awas')

@section('isi')
    <x-judul-halaman judul="Nyatakan Awas: {{ $wilayah->nama }}" keterangan="Warga langsung melihat status Awas di dashboard dan beranda. Status ini terkunci sampai Anda mencabutnya." />
    <x-kartu>
        <form method="POST" action="{{ route('admin.ews.awas.nyatakan', $wilayah) }}" class="space-y-4">
            @csrf
            <div>
                <label for="alasan" class="mb-1 block font-bold">Alasan menyatakan Awas</label>
                <textarea id="alasan" name="alasan" rows="3" required minlength="5" maxlength="500" class="isian" @error('alasan') aria-invalid="true" @enderror>{{ old('alasan') }}</textarea>
                <p class="mt-1 text-sm text-redup">Tersimpan di log aktivitas (tidak tampil ke warga). Contoh: alat berhenti mengirim saat Waspada dan kader melihat retakan baru.</p>
            </div>
            <label class="flex min-h-11 items-center gap-3 font-bold" for="konfirmasi">
                <input id="konfirmasi" type="checkbox" name="konfirmasi" value="1" required class="size-5 shrink-0">
                Saya memang ingin menyatakan {{ $wilayah->nama }} berstatus Awas.
            </label>
            <div class="flex flex-wrap gap-2">
                <x-tombol varian="bahaya">Nyatakan Awas</x-tombol>
                <x-tombol varian="kedua" :href="route('admin.ews.validasi.index')">Batal</x-tombol>
            </div>
        </form>
    </x-kartu>
@endsection
