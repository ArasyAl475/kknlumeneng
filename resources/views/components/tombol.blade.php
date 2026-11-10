@props(['varian' => 'utama', 'href' => null, 'type' => 'submit'])
{{-- Tombol seragam. Varian: utama (biru), aksen (oranye, khusus ajakan utama di halaman berfoto),
     kedua (bergaris), kaca (di atas foto), terang (putih di atas navy), bahaya (merah). --}}
@php
    // Interaksi mikro 200ms: terangkat sedikit saat disorot, panah (ikon terakhir) bergeser ke kanan.
    $gaya = 'group/tombol inline-flex min-h-11 items-center justify-center gap-2 rounded-[0.7rem] px-5 py-2.5 text-center font-judul font-bold transition-[background-color,border-color,box-shadow,transform] duration-200 ease-out motion-safe:hover:-translate-y-0.5 active:translate-y-px disabled:opacity-60 [&>svg:last-child:not(:first-child)]:transition-transform [&>svg:last-child:not(:first-child)]:duration-200 motion-safe:hover:[&>svg:last-child:not(:first-child)]:translate-x-1 '.match ($varian) {
        'kedua'  => 'border border-garis bg-permukaan text-tinta hover:border-biru hover:bg-biru-lembut hover:text-biru-tua',
        'aksen'  => 'bg-aksen text-white shadow-sm hover:bg-aksen-tua hover:shadow-md',
        'kaca'   => 'kaca-terang text-white hover:bg-white/25 focus-visible:outline-white',
        'terang' => 'bg-white text-navy shadow-sm hover:bg-biru-lembut focus-visible:outline-white',
        'bahaya' => 'bg-awas text-white shadow-sm hover:bg-awas-tua hover:shadow-md',
        default  => 'bg-biru text-white shadow-sm hover:bg-biru-tua hover:shadow-md',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($gaya) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($gaya) }}>{{ $slot }}</button>
@endif
