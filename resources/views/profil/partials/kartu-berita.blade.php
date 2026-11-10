{{-- Kartu satu berita. Seluruh kartu bisa diketuk (tautan judul direntangkan), tanpa tautan di dalam tautan.
     Variabel: $berita, $utama (opsional: kartu lebar untuk berita teratas). --}}
@php $utama = $utama ?? false; @endphp
<article @class([
    'group relative flex h-full overflow-hidden rounded-2xl border border-garis-halus bg-permukaan shadow-sm transition hover:border-kopi hover:shadow-md focus-within:ring-4 focus-within:ring-kopi/30 motion-safe:hover:-translate-y-0.5',
    'flex-col md:flex-row' => $utama,
    'flex-col' => ! $utama,
])>
    <div @class(['relative shrink-0 overflow-hidden bg-kopi/10', 'aspect-video md:aspect-auto md:w-1/2' => $utama, 'aspect-video' => ! $utama])>
        @if ($berita->urlGambar())
            <img src="{{ $berita->urlGambar() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover motion-safe:transition-transform motion-safe:duration-300 group-hover:scale-105">
        @else
            <div class="flex size-full min-h-40 items-center justify-center bg-gradient-to-br from-kopi to-kopi-tua text-white/80">
                <x-ikon nama="berita" class="size-12" />
            </div>
        @endif
        @if (($berita->foto_count ?? 0) > 0)
            <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/65 px-2.5 py-1 text-xs font-bold text-white">
                <x-ikon nama="galeri" class="size-3.5" /> {{ $berita->foto_count }} foto
            </span>
        @endif
    </div>

    <div @class(['flex flex-1 flex-col p-4 sm:p-5', 'md:p-8' => $utama])>
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-redup">
            @include('profil.partials.jenis')
            <span class="inline-flex items-center gap-1">
                <x-ikon nama="kalender" class="size-4" />
                <time datetime="{{ $berita->diterbitkan_pada?->toDateString() }}">{{ $berita->diterbitkan_pada?->format('d-m-Y') }}</time>
            </span>
        </p>
        <h3 @class(['mt-2 font-bold leading-snug text-balance', 'text-xl sm:text-2xl' => $utama, 'text-lg' => ! $utama])>
            <a href="{{ route('berita.show', $berita->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-none group-hover:text-kopi">{{ $berita->judul }}</a>
        </h3>
        <p @class(['mt-2 text-redup', 'line-clamp-4' => $utama, 'line-clamp-3' => ! $utama])>{{ $berita->ringkasan }}</p>
        <span class="mt-auto inline-flex items-center gap-1.5 pt-4 font-bold text-kopi" aria-hidden="true">
            Baca selengkapnya <x-ikon nama="panah" class="size-4 motion-safe:transition-transform group-hover:translate-x-1" />
        </span>
    </div>
</article>
