{{-- Satu modul di sidebar admin sebagai dropdown. Dipakai menu tiap modul agar semuanya seragam.
     Variabel: $kunci (penanda modul), $judul, $ikon, $butir (daftar [pola, rute, label, ikon, lencana?]).
     <details> dipakai agar dropdown tetap jalan tanpa JavaScript. Atribut name membuat hanya satu
     dropdown terbuka dalam satu waktu, supaya sidebar tidak memanjang dan membingungkan.
     $tempatMenu dibedakan antara menu HP dan layar lebar: keduanya ada di halaman yang sama, dan
     dropdown bernama sama di dua menu akan saling menutup. --}}
@php
    $terbuka = request()->routeIs(...collect($butir)->pluck(0)->flatten()->all());
    $jumlahLencana = collect($butir)->sum(fn (array $b) => (int) ($b[4] ?? 0));
@endphp
<details data-menu="{{ $kunci }}" name="menu-admin-{{ $tempatMenu ?? 'utama' }}" class="group/grup" @if ($terbuka) open @endif>
    <summary @class(['menu-admin__item', 'text-white' => $terbuka])>
        @include('layouts.partials.ikon-menu', ['nama' => $ikon])
        <span class="menu-admin__label font-bold">{{ $judul }}</span>
        @if ($jumlahLencana > 0)
            <span class="menu-admin__lencana">{{ $jumlahLencana }}<span class="sr-only"> menunggu</span></span>
        @endif
        <svg class="size-5 shrink-0 opacity-70 motion-safe:transition-transform group-open/grup:rotate-180" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <ul class="mb-2 ml-5 mt-0.5 space-y-0.5 border-l border-white/15 pb-1 pl-2">
        @foreach ($butir as $b)
            @include('layouts.partials.item-menu-admin', ['pola' => $b[0], 'rute' => $b[1], 'label' => $b[2], 'ikon' => $b[3], 'lencana' => $b[4] ?? null])
        @endforeach
    </ul>
</details>
