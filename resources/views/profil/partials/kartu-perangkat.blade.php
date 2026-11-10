{{-- Kartu satu perangkat desa. Variabel: $orang (Perangkat), $besar (opsional, untuk kepala desa). --}}
@php $besar = $besar ?? false; @endphp
<div @class([
    'flex items-center gap-4 rounded-2xl border border-garis-halus bg-permukaan p-3 shadow-sm',
    'flex-col p-5 text-center sm:p-6' => $besar,
])>
    @if ($orang->urlFoto())
        <img src="{{ $orang->urlFoto() }}" alt="Foto {{ $orang->nama }}" loading="lazy" decoding="async"
             @class(['shrink-0 rounded-full object-cover ring-4 ring-kopi/15', 'size-28 sm:size-32' => $besar, 'size-16' => ! $besar])>
    @else
        <span aria-hidden="true" @class([
            'flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-kopi to-kopi-tua font-bold text-white ring-4 ring-kopi/15',
            'size-28 text-3xl sm:size-32' => $besar,
            'size-16 text-lg' => ! $besar,
        ])>{{ $orang->inisial() }}</span>
    @endif
    <div class="min-w-0">
        <p @class(['font-bold leading-snug', 'text-xl' => $besar])>{{ $orang->nama }}</p>
        <p @class(['text-redup', 'mt-1 inline-block rounded-full bg-kopi/10 px-3 py-0.5 text-sm font-bold text-kopi' => $besar, 'text-sm' => ! $besar])>{{ $orang->jabatan }}</p>
    </div>
</div>
