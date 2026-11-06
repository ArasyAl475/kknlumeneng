{{-- Kotak isian banyak baris; x-bidang bersama hanya mendukung input satu baris. --}}
<div>
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    <textarea id="{{ $nama }}" name="{{ $nama }}" rows="{{ $baris ?? 4 }}" maxlength="{{ $maks ?? 5000 }}" @class(['isian']) @if ($wajib ?? false) required @endif @if ($bantuan ?? false) aria-describedby="{{ $nama }}-bantuan" @endif @error($nama) aria-invalid="true" @enderror>{{ $nilai }}</textarea>
    @if ($bantuan ?? false)
        <p id="{{ $nama }}-bantuan" class="mt-1 text-sm text-redup">{{ $bantuan }}</p>
    @endif
    @error($nama)
        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
</div>
