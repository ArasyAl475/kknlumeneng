@props(['judul', 'keterangan' => null])
<header {{ $attributes->class('mb-6 flex flex-wrap items-end justify-between gap-3') }}>
    <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1">
            <div class="h-1 w-5 rounded-full bg-kopi"></div>
        </div>
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $judul }}</h1>
        @if ($keterangan)
            <p class="mt-1 max-w-2xl text-sm text-redup">{{ $keterangan }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</header>
