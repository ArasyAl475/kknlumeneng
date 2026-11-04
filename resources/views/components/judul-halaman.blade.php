@props(['judul', 'keterangan' => null])
<header {{ $attributes->class('mb-5 flex flex-wrap items-end justify-between gap-3') }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $judul }}</h1>
        @if ($keterangan)
            <p class="mt-1 max-w-2xl text-redup">{{ $keterangan }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</header>
