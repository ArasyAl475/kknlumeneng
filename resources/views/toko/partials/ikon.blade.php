{{-- Ikon khusus modul Toko. Dekoratif: maknanya selalu ada pada teks di sebelahnya. --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{{ $kelas ?? 'size-6' }} shrink-0" aria-hidden="true" focusable="false">
    @switch($nama)
        @case('kopi')
            <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM17 10h1.5a2.5 2.5 0 0 1 0 5H17M8 3v3M12.5 3v3" />
            @break
        @case('jamur')
            <path d="M3.5 12a8.5 8.5 0 0 1 17 0ZM9.5 12v6a2.5 2.5 0 0 0 5 0v-6" />
            @break
        @case('uang')
            <rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" />
            @break
        @case('panah')
            <path d="m6 9 6 6 6-6" />
            @break
    @endswitch
</svg>
