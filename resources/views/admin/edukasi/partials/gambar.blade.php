{{-- Pilihan gambar untuk materi dan langkah. $url berisi gambar yang sedang dipakai, jika ada. --}}
@php
    $batas = \App\Services\Edukasi\PengolahGambar::teksBatas();
@endphp
<div>
    <label for="gambar" class="mb-1 block font-bold">{{ $label ?? 'Gambar (boleh dikosongkan)' }}</label>
    @if ($url)
        <img src="{{ $url }}" alt="Gambar yang sedang dipakai" class="mb-2 max-h-48 rounded-md border border-garis-halus">
    @endif
    <input id="gambar" name="gambar" type="file" accept="image/jpeg,image/png,image/webp" class="isian" data-maks-kb="{{ \App\Services\Edukasi\PengolahGambar::maksimalKb() }}" aria-describedby="gambar-bantuan" @error('gambar') aria-invalid="true" @enderror>
    <p id="gambar-bantuan" class="mt-1 text-sm text-redup">Format JPG, PNG, atau WebP, paling besar {{ $batas }}. Foto otomatis dikecilkan agar ringan dibuka warga.</p>
    <p id="gambar-peringatan" class="mt-1 text-sm font-bold text-awas" role="alert" hidden>Ukuran foto lebih dari {{ $batas }}. Pilih foto yang lebih kecil.</p>
    @error('gambar')
        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
    @if ($url)
        <label class="mt-2 flex min-h-11 items-center gap-2">
            <input type="checkbox" name="hapus_gambar" value="1" class="size-5" @checked(old('hapus_gambar'))>
            Hapus gambar ini
        </label>
    @endif
</div>
