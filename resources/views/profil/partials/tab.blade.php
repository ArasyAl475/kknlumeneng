{{-- Menu loncat Profil Desa: tiga bagian dalam satu halaman. Menempel di atas saat digulir, dan bagian
     yang sedang dibaca menyala (resources/js/app.js, [data-sorot-bagian]). Tanpa JavaScript tetap tautan jangkar biasa. --}}
@php
    $tab = [
        ['tentang', 'Tentang Desa', 'desa'],
        ...($potensi->isNotEmpty() ? [['potensi', 'Potensi', 'daun']] : []),
        ['visi-misi', 'Visi & Misi', 'aman'],
        ['pemerintahan', 'Pemerintahan', 'orang'],
    ];
@endphp
<nav aria-label="Bagian profil desa" data-sorot-bagian class="sticky top-[4.75rem] z-30 -mt-10 mb-12 sm:mx-8">
    <ul class="tanpa-batang-gulir flex gap-1 overflow-x-auto rounded-2xl bg-permukaan/95 p-1.5 shadow-angkat ring-1 ring-garis-halus backdrop-blur">
        @foreach ($tab as [$jangkar, $label, $ikon])
            <li class="flex-1">
                <a href="#{{ $jangkar }}" @if ($loop->first) aria-current="true" @endif
                   class="flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 font-judul font-bold text-redup transition-colors duration-200 hover:bg-biru-lembut hover:text-biru aria-[current=true]:bg-biru aria-[current=true]:text-white aria-[current=true]:shadow-sm">
                    <x-ikon :nama="$ikon" class="size-5" />{{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
