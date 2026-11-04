@props(['nama', 'label', 'bantuan' => null])
{{-- Isian formulir dengan label dan pesan galat yang terhubung ke input. --}}
<div {{ $attributes->only('class') }}>
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    <input id="{{ $nama }}" name="{{ $nama }}" {{ $attributes->except('class')->merge(['type' => 'text', 'value' => old($nama)])->class('isian') }}
        @error($nama) aria-invalid="true" aria-describedby="{{ $nama }}-galat" @enderror>
    @if ($bantuan)
        <p class="mt-1 text-sm text-redup">{{ $bantuan }}</p>
    @endif
    @error($nama)
        <p id="{{ $nama }}-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
</div>
