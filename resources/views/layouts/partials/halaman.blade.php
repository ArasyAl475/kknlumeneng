{{-- Pagination bernomor dari server (target sentuh 44px). Tampilannya sama dengan pagination di browser
     ([data-paginasi], resources/js/app.js): Sebelumnya, nomor halaman dengan "…", Berikutnya. --}}
@if ($paginator->hasPages())
    @php
        $aktif = $paginator->currentPage();
        $total = $paginator->lastPage();
        $nomor = collect([1, $total, $aktif - 1, $aktif, $aktif + 1])->filter(fn ($n) => $n >= 1 && $n <= $total)->unique()->sort()->values();
        $panahKiri = '<svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $panahKanan = '<svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    @endphp
    <nav aria-label="Navigasi halaman" class="paginasi">
        @if ($paginator->onFirstPage())
            <span class="paginasi__tombol opacity-45" aria-disabled="true">{!! $panahKiri !!}<span class="sr-only sm:not-sr-only">Sebelumnya</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="paginasi__tombol">{!! $panahKiri !!}<span class="sr-only sm:not-sr-only">Sebelumnya</span></a>
        @endif

        <span class="paginasi__nomor">
            @foreach ($nomor as $i => $n)
                @if ($i > 0 && $n - $nomor[$i - 1] > 1)
                    <span class="paginasi__elipsis" aria-hidden="true">…</span>
                @endif
                @if ($n === $aktif)
                    <span class="paginasi__tombol paginasi__tombol--nomor" aria-current="page">{{ $n }}</span>
                @else
                    <a href="{{ $paginator->url($n) }}" class="paginasi__tombol paginasi__tombol--nomor" aria-label="Halaman {{ $n }}">{{ $n }}</a>
                @endif
            @endforeach
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="paginasi__tombol"><span class="sr-only sm:not-sr-only">Berikutnya</span>{!! $panahKanan !!}</a>
        @else
            <span class="paginasi__tombol opacity-45" aria-disabled="true"><span class="sr-only sm:not-sr-only">Berikutnya</span>{!! $panahKanan !!}</span>
        @endif

        <span class="paginasi__ket">Halaman {{ $aktif }} dari {{ $total }}</span>
    </nav>
@endif
