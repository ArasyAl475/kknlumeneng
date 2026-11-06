{{-- Satu butir menu sidebar admin. Dipakai menu tiap modul agar tampilannya seragam.
     Variabel: $pola (pola nama rute aktif), $rute, $label, $ikon, $lencana (opsional, angka). --}}
@php $aktif = request()->routeIs($pola); @endphp
<li>
    <a href="{{ route($rute) }}" @class(['menu-admin__item', 'is-aktif' => $aktif]) @if ($aktif) aria-current="page" @endif>
        @include('layouts.partials.ikon-menu', ['nama' => $ikon])
        <span class="menu-admin__label">{{ $label }}</span>
        @if (! empty($lencana))
            <span class="menu-admin__lencana">{{ $lencana }}<span class="sr-only"> menunggu</span></span>
        @endif
    </a>
</li>
