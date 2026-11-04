@props(['nama'])
{{-- Bentuk ikon ada di ikon-sprite, supaya Blade dan JavaScript memakai gambar yang sama. --}}
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} aria-hidden="true" focusable="false"><use href="#i-{{ $nama }}"></use></svg>
