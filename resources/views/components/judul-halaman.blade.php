@props(['judul', 'keterangan' => null])
{{-- Judul halaman sederhana (panel admin dan halaman publik tanpa foto). --}}
<header {{ $attributes->class('mb-6 flex flex-wrap items-end justify-between gap-3') }}>
    <div class="min-w-0">
        <span class="mb-2 block h-1 w-8 rounded-full bg-aksen" aria-hidden="true"></span>
        <h1 class="text-2xl font-extrabold text-navy sm:text-3xl">{{ $judul }}</h1>
        @if ($keterangan)
            <p class="mt-1 max-w-2xl text-redup">{{ $keterangan }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</header>
