{{-- Kartu satu perangkat desa. Variabel: $orang (Perangkat), $besar (opsional, kepala desa), $tunggal (opsional, kartu
     yang berdiri sendiri di tengah bagan: kepala dan sekretaris desa).
     Dengan foto: kartu tegak, foto potret di atas, nama dan jabatan di bawahnya.
     Tanpa foto: kartu mendatar dengan huruf awal nama, seperti semula. --}}
@php
    $besar = $besar ?? false;
    $tunggal = $tunggal ?? false;
    $foto = $orang->urlFoto();
    // Kartu foto yang berdiri sendiri dibatasi lebarnya agar fotonya tidak membesar seukuran layar.
    $batas = $besar ? 'mx-auto max-w-xs' : ($tunggal ? 'mx-auto max-w-[16rem]' : '');
@endphp
@if ($foto)
    <figure @class(['group flex h-full w-full flex-col overflow-hidden rounded-2xl border border-garis-halus bg-permukaan shadow-kartu', $batas, 'shadow-angkat' => $besar])>
        {{-- Bingkai 4:5 dipesan agar halaman tidak melompat saat foto dimuat; object-top menjaga wajah tetap terlihat. --}}
        <div class="aspect-[4/5] w-full overflow-hidden bg-biru-lembut">
            <img src="{{ $foto }}" alt="Foto {{ $orang->nama }}" loading="lazy" decoding="async" width="800" height="1000"
                 class="size-full object-cover object-top motion-safe:transition-transform motion-safe:duration-500 group-hover:scale-105">
        </div>
        <figcaption @class(['flex flex-1 flex-col items-center justify-center p-3 text-center', 'p-5' => $besar])>
            <span @class(['font-bold leading-snug', 'text-xl' => $besar])>{{ $orang->nama }}</span>
            <span @class(['mt-1 text-redup', 'inline-block rounded-full bg-kopi/10 px-3 py-0.5 text-sm font-bold text-kopi' => $besar, 'text-sm' => ! $besar])>{{ $orang->jabatan }}</span>
        </figcaption>
    </figure>
@else
    <div @class([
        'flex w-full items-center gap-4 rounded-2xl border border-garis-halus bg-permukaan p-3 shadow-sm',
        'flex-col p-5 text-center sm:p-6' => $besar,
        $batas,
    ])>
        <span aria-hidden="true" @class([
            'flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-kopi to-kopi-tua font-bold text-white ring-4 ring-kopi/15',
            'size-28 text-3xl sm:size-32' => $besar,
            'size-16 text-lg' => ! $besar,
        ])>{{ $orang->inisial() }}</span>
        <div class="min-w-0">
            <p @class(['font-bold leading-snug', 'text-xl' => $besar])>{{ $orang->nama }}</p>
            <p @class(['text-redup', 'mt-1 inline-block rounded-full bg-kopi/10 px-3 py-0.5 text-sm font-bold text-kopi' => $besar, 'text-sm' => ! $besar])>{{ $orang->jabatan }}</p>
        </div>
    </div>
@endif
