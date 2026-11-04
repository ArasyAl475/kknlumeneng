@props(['varian' => 'utama', 'href' => null, 'type' => 'submit'])
@php
    $gaya = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-4 py-2 text-center font-bold transition-colors '.match ($varian) {
        'kedua' => 'border border-garis bg-permukaan text-tinta hover:bg-kosong-lembut active:bg-garis-halus',
        'bahaya' => 'bg-awas text-white hover:bg-awas-tua active:bg-awas-tua',
        default => 'bg-kopi text-white hover:bg-kopi-tua active:bg-kopi-tua',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($gaya) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($gaya) }}>{{ $slot }}</button>
@endif
