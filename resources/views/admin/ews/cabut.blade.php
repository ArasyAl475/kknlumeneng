@extends('layouts.admin')
@section('judul', 'Cabut status Awas')

@section('isi')
    <x-judul-halaman judul="Cabut status Awas: {{ $wilayah->nama }}" keterangan="Setelah dicabut, status wilayah kembali mengikuti data sensor. Jika alat masih melaporkan kabel putus, wilayah akan langsung Awas lagi." />
    <x-kartu>
        <p><span class="font-bold">Penyebab:</span> {{ $wilayah->awas_sumber }}</p>
        <p class="mb-4"><span class="font-bold">Sejak:</span> {{ $wilayah->awas_sejak?->format('d-m-Y H:i') }} WIB</p>
        <form method="POST" action="{{ route('admin.ews.awas.cabut', $wilayah) }}" class="space-y-4">
            @csrf
            <div>
                <label for="alasan" class="mb-1 block font-bold">Alasan mencabut</label>
                <textarea id="alasan" name="alasan" rows="3" required minlength="5" maxlength="500" class="isian" @error('alasan') aria-invalid="true" @enderror>{{ old('alasan') }}</textarea>
                <p class="mt-1 text-sm text-redup">Tersimpan di log aktivitas. Contoh: kabel sudah diperbaiki dan lereng sudah diperiksa kader.</p>
            </div>
            <label class="flex min-h-11 items-center gap-3 font-bold" for="konfirmasi">
                <input id="konfirmasi" type="checkbox" name="konfirmasi" value="1" required class="size-5 shrink-0">
                Saya sudah memastikan situasi di lapangan aman.
            </label>
            <div class="flex flex-wrap gap-2">
                <x-tombol varian="bahaya">Cabut status Awas</x-tombol>
                <x-tombol varian="kedua" :href="route('admin.ews.validasi.index')">Batal</x-tombol>
            </div>
        </form>
    </x-kartu>
@endsection
