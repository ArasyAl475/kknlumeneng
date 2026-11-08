{{-- Ikon menu admin (satu set garis). Dekoratif: maknanya selalu ada pada teks di sebelahnya. --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="menu-admin__ikon" aria-hidden="true" focusable="false">
    @switch($nama)
        @case('beranda')
            <rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />
            @break
        @case('validasi')
            <path d="M12 3 4 6v6c0 4.5 3.4 7.7 8 9 4.6-1.3 8-4.5 8-9V6l-8-3Z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('alat')
            <path d="M2 8.5a15 15 0 0 1 20 0M5.5 12.5a10 10 0 0 1 13 0M9 16a4.5 4.5 0 0 1 6 0M12 20h.01" />
            @break
        @case('wilayah')
            <path d="M12 22s7.5-7 7.5-12.5a7.5 7.5 0 0 0-15 0C4.5 15 12 22 12 22Z" /><circle cx="12" cy="9.5" r="2.5" />
            @break
        @case('ambang')
            <path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h10M18 18h2" /><circle cx="16" cy="6" r="2" /><circle cx="10" cy="12" r="2" /><circle cx="16" cy="18" r="2" />
            @break
        @case('riwayat')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('log')
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            @break
        @case('simulator')
            <path d="M8 5v14l11-7Z" />
            @break
        @case('kopi')
            <g transform="rotate(35 12 12)"><ellipse cx="12" cy="12" rx="5.5" ry="9" /><path d="M12 3.5c-2.2 3 2.2 5.5 0 8.5s2.2 5.5 0 8.5" /></g>
            @break
        @case('jamur')
            <path d="M3 12.5a9 8.5 0 0 1 18 0Z" /><path d="M9.5 12.5V18a2.5 2.5 0 0 0 5 0v-5.5" /><path d="M8 9h.01M12.5 7h.01M16 10h.01" />
            @break
        @case('penjualan')
            <path d="M4 10v10h16V10M3 10l2-6h14l2 6M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
            @break
        @case('produk')
            <path d="M21 8 12 3 3 8v8l9 5 9-5V8Z" /><path d="m3 8 9 5 9-5M12 13v8" />
            @break
        @case('profil')
            <circle cx="12" cy="8" r="4" /><path d="M20 21a8 8 0 0 0-16 0" />
            @break
        @case('situs')
            <path d="M3 12 12 3l9 9v9H3z" /><path d="M9 21v-9h6v9" />
            @break
        @case('keluar')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
            @break
        @default
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
