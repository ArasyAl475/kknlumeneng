@props(['judul' => null])
<section {{ $attributes->class('rounded-lg border border-garis-halus bg-permukaan p-4 sm:p-5') }}>
    @if ($judul)
        <h2 class="mb-3 text-lg font-bold">{{ $judul }}</h2>
    @endif
    {{ $slot }}
</section>
