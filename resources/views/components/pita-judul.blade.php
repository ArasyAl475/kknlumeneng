@props(['judul', 'keterangan' => null, 'gambar' => null, 'label' => null, 'ikon' => null, 'jejak' => [], 'ringkas' => false])
{{-- Pita judul halaman data: foto latar (slot Desain Web) + lapisan navy + jejak halaman + judul tegas.
     Dipakai di @section('hero') agar lebarnya penuh layar. $jejak: [['Label', url|null], ...].
     $ringkas: lebih pendek, untuk halaman yang isinya harus segera terlihat (Peringatan Dini). --}}
<section {{ $attributes->class(['relative isolate overflow-hidden bg-navy text-white']) }}>
    @if ($gambar)
        <x-gambar-situs :kunci="$gambar" :prioritas="true" class="absolute inset-0 -z-20 size-full" />
    @endif
    <div class="lapis-pita absolute inset-0 -z-10" aria-hidden="true"></div>
    <div class="pola-kontur absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>

    <div @class([
        'mx-auto max-w-6xl px-4',
        'pb-7 pt-5 sm:pb-9 sm:pt-6' => $ringkas,
        'pb-10 pt-6 sm:pb-14 sm:pt-8' => ! $ringkas,
    ])>
        <nav aria-label="Jejak halaman" class="text-sm">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-white/75">
                <li><a href="{{ route('landing') }}" class="inline-flex min-h-8 items-center gap-1.5 rounded hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="beranda" class="size-4" />Beranda</a></li>
                @foreach ($jejak as [$teks, $url])
                    <li class="flex items-center gap-2" @if ($loop->last) aria-current="page" @endif>
                        <svg class="size-3.5 opacity-60" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @if ($url && ! $loop->last)
                            <a href="{{ $url }}" class="inline-flex min-h-8 items-center rounded hover:text-white hover:underline focus-visible:outline-white">{{ $teks }}</a>
                        @else
                            <span class="font-bold text-white">{{ $teks }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0 max-w-3xl">
                @if ($label)
                    <p class="kicker kicker--terang">
                        @if ($ikon)<x-ikon :nama="$ikon" class="size-4" />@endif
                        {{ $label }}
                    </p>
                @endif
                <h1 @class([
                    'mt-2 font-extrabold leading-[1.1] text-balance',
                    'text-[clamp(1.6rem,5vw,2.4rem)]' => $ringkas,
                    'text-[clamp(1.9rem,6vw,3rem)]' => ! $ringkas,
                ])>{{ $judul }}</h1>
                @if ($keterangan)
                    <p class="mt-2 max-w-2xl text-base text-white/85 sm:text-lg">{{ $keterangan }}</p>
                @endif
            </div>
            @if ($slot->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
