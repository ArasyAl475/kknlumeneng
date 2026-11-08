@props(['varian' => 'utama', 'href' => null, 'type' => 'submit'])
@php
    $gaya = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-center font-bold transition-all active:scale-95 '.match ($varian) {
        'kedua'  => 'border-2 border-garis bg-permukaan text-tinta hover:border-kopi hover:bg-kopi/5 active:bg-garis-halus',
        'bahaya' => 'bg-awas text-white hover:bg-awas-tua shadow-sm hover:shadow-md',
        default  => 'bg-kopi text-white hover:bg-kopi-tua shadow-sm hover:shadow-md',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($gaya) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($gaya) }}>{{ $slot }}</button>
@endif
