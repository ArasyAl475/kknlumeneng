@php
    $contoh = \App\Models\Toko\Produk::SARAN_SPESIFIKASI[$b['label'] ?? ''] ?? 'contoh: 250 g';
@endphp
<fieldset class="grid gap-2 rounded-md border border-garis-halus p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)_auto] sm:items-end">
    <legend class="sr-only">Baris spesifikasi</legend>
    <div>
        <label for="spesifikasi-{{ $i }}-label" class="mb-1 block text-sm font-bold">Judul</label>
        <input id="spesifikasi-{{ $i }}-label" name="spesifikasi[{{ $i }}][label]" maxlength="60" class="isian" list="saran-spesifikasi"
            placeholder="contoh: Berat" value="{{ $b['label'] ?? '' }}"
            @error("spesifikasi.{$i}.label") aria-invalid="true" aria-describedby="spesifikasi-{{ $i }}-label-galat" @enderror>
        @error("spesifikasi.{$i}.label")
            <p id="spesifikasi-{{ $i }}-label-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="spesifikasi-{{ $i }}-nilai" class="mb-1 block text-sm font-bold">Isi</label>
        <input id="spesifikasi-{{ $i }}-nilai" name="spesifikasi[{{ $i }}][nilai]" maxlength="200" class="isian"
            placeholder="{{ $contoh }}" value="{{ $b['nilai'] ?? '' }}"
            @error("spesifikasi.{$i}.nilai") aria-invalid="true" aria-describedby="spesifikasi-{{ $i }}-nilai-galat" @enderror>
        @error("spesifikasi.{$i}.nilai")
            <p id="spesifikasi-{{ $i }}-nilai-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
    {{-- Tanpa JavaScript tombol ini tersembunyi; mengosongkan isi baris sudah cukup untuk membuangnya. --}}
    <x-tombol varian="kedua" type="button" class="px-3" data-hapus-baris hidden>Hapus</x-tombol>
</fieldset>
