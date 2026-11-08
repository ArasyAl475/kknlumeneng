@props(['status' => null])
{{-- Status selalu warna + ikon + kata. Status lain/kosong menjadi "Belum ada data". --}}
@php
    [$warna, $ikon, $kata] = match ($status) {
        'Aman'    => ['hijau', 'aman',    'Aman'],
        'Waspada' => ['kuning', 'waspada', 'Waspada'],
        'Awas'    => ['merah', 'awas',    'Awas'],
        default   => ['kosong', 'kosong', 'Belum ada data'],
    };
@endphp
<span data-warna="{{ $warna }}" {{ $attributes->class('status-pekat inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-bold whitespace-nowrap') }}>
    <x-ikon :nama="$ikon" class="size-4 shrink-0" />{{ $kata }}
</span>
