{{-- Logo desa dari menu Desain Web (slot "situs.logo"). Selalu berdampingan dengan tulisan "Desa Lumeneng",
     jadi alt kosong: pembaca layar tidak perlu membaca nama desa dua kali. --}}
<img src="{{ site_image('situs.logo') }}" alt="" decoding="async" {{ $attributes->merge(['class' => 'shrink-0 rounded-xl object-contain']) }}>
