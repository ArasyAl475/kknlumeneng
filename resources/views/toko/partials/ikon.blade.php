{{-- Ikon khusus modul Toko, satu set garis yang sama. Dekoratif: maknanya selalu ada pada teks di sebelahnya. --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{{ $kelas ?? 'size-6' }} shrink-0" aria-hidden="true" focusable="false">
    @switch($nama)
        @case('kopi')
            <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM17 10h1.5a2.5 2.5 0 0 1 0 5H17M8 3v3M12.5 3v3" />
            @break
        @case('jamur')
            <path d="M3 12.5a9 8.5 0 0 1 18 0Z" /><path d="M9.5 12.5V18a2.5 2.5 0 0 0 5 0v-5.5" /><path d="M8 9h.01M12.5 7h.01M16 10h.01" />
            @break
        @case('biji-kopi')
            <g transform="rotate(35 12 12)"><ellipse cx="12" cy="12" rx="5.5" ry="9" /><path d="M12 3.5c-2.2 3 2.2 5.5 0 8.5s2.2 5.5 0 8.5" /></g>
            @break
        @case('limbah')
            <path d="M20 4C10 4 5 9 5 15c0 1.8.4 3.3 1 5 6 0 14-4 14-16ZM6 20l9-9" />
            @break
        @case('uang')
            <rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" />
            @break
        @case('terjual')
            <path d="M4 10v10h16V10M3 10l2-6h14l2 6M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
            @break
        @case('panah')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('panah-kanan')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('naik')
            <path d="M12 19V5M6 11l6-6 6 6" />
            @break
        @case('turun')
            <path d="M12 5v14M6 13l6 6 6-6" />
            @break
        @case('tetap')
            <path d="M5 12h14" />
            @break
        @case('filter')
            <path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h10M18 18h2" /><circle cx="16" cy="6" r="2" /><circle cx="10" cy="12" r="2" /><circle cx="16" cy="18" r="2" />
            @break
        @case('tutup')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('tabel')
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 10h18M9 4v16" />
            @break
        @case('grafik')
            <path d="M5 20v-7M12 20V5M19 20V9" />
            @break
        @case('peringatan')
            <path d="M12 3 2 21h20L12 3ZM12 10v5M12 18h.01" />
            @break
        @case('terverifikasi')
            <circle cx="12" cy="12" r="9" /><path d="m8 12.5 3 3 5-6" />
            @break
        @case('minus-bulat')
            <circle cx="12" cy="12" r="9" /><path d="M8 12h8" />
            @break
        @case('pohon')
            <path d="M12 3 6.5 12H10l-3 5.5h10L14 12h3.5L12 3ZM12 17.5V21" />
            @break
        @case('centang')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @case('urut')
            <path d="M8 5v14M4 9l4-4 4 4M16 19V5M12 15l4 4 4-4" />
            @break
    @endswitch
</svg>
