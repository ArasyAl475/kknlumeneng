{{-- Paginasi ringkas dengan target sentuh 44px, karena bawaan Laravel terlalu kecil untuk HP. --}}
@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span></span>
        @else
            <x-tombol varian="kedua" :href="$paginator->previousPageUrl()" rel="prev">Sebelumnya</x-tombol>
        @endif
        <span class="text-sm text-redup">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <x-tombol varian="kedua" :href="$paginator->nextPageUrl()" rel="next">Berikutnya</x-tombol>
        @else
            <span></span>
        @endif
    </nav>
@endif
