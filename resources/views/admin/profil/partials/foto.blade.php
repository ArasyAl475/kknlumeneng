{{-- Pilihan foto. Variabel: $nama (nama isian), $label, $url (foto yang sedang dipakai, boleh null), $bantuan (opsional). --}}
@php $batas = \App\Services\Profil\PenyimpanGambar::teksBatas(); @endphp
<div>
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    @if ($url)
        <img src="{{ $url }}" alt="Foto yang sedang dipakai" class="mb-2 max-h-48 rounded-xl border border-garis-halus object-cover">
    @endif
    <input id="{{ $nama }}" name="{{ $nama }}" type="file" accept="image/jpeg,image/png,image/webp" class="isian"
        data-maks-kb="{{ \App\Services\Profil\PenyimpanGambar::maksimalKb() }}" aria-describedby="{{ $nama }}-bantuan"
        @error($nama) aria-invalid="true" @enderror>
    <p id="{{ $nama }}-bantuan" class="mt-1 text-sm text-redup">{{ $bantuan ?? '' }} Format JPG, PNG, atau WebP, paling besar {{ $batas }}. Foto otomatis dikecilkan agar ringan dibuka warga.</p>
    <p id="{{ $nama }}-peringatan" class="mt-1 text-sm font-bold text-awas" role="alert" hidden>Ukuran foto lebih dari {{ $batas }}. Pilih foto yang lebih kecil.</p>
    @error($nama)
        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
    @if ($url)
        <label class="mt-2 flex min-h-11 items-center gap-2">
            <input type="checkbox" name="hapus_{{ $nama }}" value="1" class="size-5" @checked(old('hapus_'.$nama))>
            Hapus foto ini
        </label>
    @endif
</div>
