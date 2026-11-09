{{-- Label jenis tulisan. Warna kuning/merah sengaja tidak dipakai karena itu khusus status bahaya EWS. --}}
@php
    $gaya = match ($berita->jenis) {
        'pengumuman' => 'bg-secondary-fixed text-on-secondary-fixed',
        'artikel' => 'bg-primary-fixed text-on-primary-fixed',
        default => 'bg-kopi text-white',
    };
@endphp
<span class="{{ $gaya }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide">{{ $berita->labelJenis() }}</span>
