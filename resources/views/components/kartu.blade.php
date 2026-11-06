@props(['judul' => null])
<section {{ $attributes->class('overflow-hidden rounded-2xl border-2 border-garis-halus bg-permukaan p-5 shadow-sm sm:p-6') }}>
    @if ($judul)
        <h2 class="mb-4 text-lg font-bold">{{ $judul }}</h2>
    @endif
    {{ $slot }}
</section>
