@props(['kunci', 'prioritas' => false])
{{-- Gambar tata letak dari menu Desain Web. Contoh: <x-gambar-situs kunci="beranda.hero" class="size-full object-cover" />
     Alt kosong = hiasan, dilewati pembaca layar. Gambar bawaan otomatis dipakai bila slot kosong atau berkasnya hilang. --}}
<img src="{{ site_image($kunci) }}" alt="{{ site_image_alt($kunci) }}" decoding="async"
     @if ($prioritas) fetchpriority="high" @else loading="lazy" @endif
     {{ $attributes->merge(['class' => 'object-cover']) }}>
