{{-- Pilih banyak foto sekaligus. Foto dikecilkan di HP sebelum dikirim (resources/js/profil/admin.js).
     Variabel: $nama (nama isian tanpa []), $label, $bantuan (opsional), $wajib (opsional), $maks (opsional, batas jumlah foto). --}}
@php
    $batas = \App\Services\Profil\PenyimpanGambar::teksBatas();
    $maks ??= \App\Http\Requests\Profil\BeritaRequest::MAKS_FOTO_SEKALIGUS;
    $galat = $errors->first($nama) ?: collect($errors->getMessages())->filter(fn ($p, $k) => str_starts_with($k, $nama.'.'))->flatten()->first();
@endphp
<div>
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    <input id="{{ $nama }}" name="{{ $nama }}[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="isian"
        data-maks-kb="{{ \App\Services\Profil\PenyimpanGambar::maksimalKb() }}" aria-describedby="{{ $nama }}-bantuan {{ $nama }}-status"
        @if ($wajib ?? false) required @endif
        @if ($galat) aria-invalid="true" @endif>
    <p id="{{ $nama }}-bantuan" class="mt-1 text-sm text-redup">{{ $bantuan ?? '' }} Boleh pilih beberapa foto sekaligus (paling banyak {{ $maks }}). Format JPG, PNG, atau WebP, paling besar {{ $batas }} per foto.</p>
    <p id="{{ $nama }}-status" class="mt-1 text-sm font-bold text-kopi" role="status" aria-live="polite"></p>
    <p id="{{ $nama }}-peringatan" class="mt-1 text-sm font-bold text-awas" role="alert" hidden>Sebagian foto lebih dari {{ $batas }} dan tidak ikut dikirim.</p>
    <div id="{{ $nama }}-pratinjau" class="mt-2 grid grid-cols-4 gap-2 empty:hidden sm:grid-cols-6"></div>
    @if ($galat)
        <p class="mt-1 text-sm font-bold text-awas">{{ $galat }}</p>
    @endif
</div>
