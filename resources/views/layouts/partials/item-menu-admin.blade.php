{{-- Satu butir menu sidebar admin. Dipakai menu tiap modul agar tampilannya seragam.
     Variabel: $pola (pola nama rute aktif), $rute, $label, $ikon, $lencana (opsional, angka),
     $sub (opsional, true untuk butir di dalam dropdown: tanpa ikon agar daftar tidak ramai). --}}
@php $aktif = request()->routeIs(...(array) $pola); @endphp
<li>
    <a href="{{ route($rute) }}" @class(['menu-admin__item', 'menu-admin__item--sub' => $sub ?? false, 'is-aktif' => $aktif]) @if ($aktif) aria-current="page" @endif>
        @unless ($sub ?? false)
            @include('layouts.partials.ikon-menu', ['nama' => $ikon])
        @endunless
        <span class="menu-admin__label">{{ $label }}</span>
        @if (! empty($lencana))
            <span class="menu-admin__lencana">{{ $lencana }}<span class="sr-only"> menunggu</span></span>
        @endif
    </a>
</li>
