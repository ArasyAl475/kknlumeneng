{{-- Status tulisan selalu warna + ikon + kata. --}}
@if ($berita->sudahTerbit())
    <span data-warna="hijau" class="status-pekat inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-sm font-bold whitespace-nowrap"><x-ikon nama="aman" class="size-4 shrink-0" />Terbit</span>
@else
    <span data-warna="kosong" class="status-pekat inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-sm font-bold whitespace-nowrap"><x-ikon nama="kosong" class="size-4 shrink-0" />Draf</span>
@endif
