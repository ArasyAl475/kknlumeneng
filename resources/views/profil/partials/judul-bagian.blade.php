{{-- Judul bagian dengan garis aksen oranye. Variabel: $id, $judul, $keterangan (opsional). --}}
<div class="mb-5">
    <div class="flex items-center gap-3">
        <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
        <h2 id="{{ $id }}" class="text-2xl font-extrabold text-navy sm:text-[1.7rem]">{{ $judul }}</h2>
    </div>
    @if (! empty($keterangan))
        <p class="mt-1 text-redup">{{ $keterangan }}</p>
    @endif
</div>
