@props(['judul' => null])
{{-- Kartu data: putih, garis tipis, sudut 14px, bayangan lembut. Tidak untuk diletakkan di dalam kartu lain. --}}
<section {{ $attributes->class('overflow-hidden rounded-[0.9rem] border border-garis-halus bg-permukaan p-5 shadow-kartu sm:p-6') }}>
    @if ($judul)
        <h2 class="mb-4 text-lg font-bold">{{ $judul }}</h2>
    @endif
    {{ $slot }}
</section>
