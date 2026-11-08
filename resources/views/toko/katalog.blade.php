<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Katalog Produk Sederhana · Koci Circular Desa Lumeneng</title>
    <meta name="description" content="Katalog produk sirkular Koci: jamur tiram putih organik, bibit baglog sirkular, kopi konservasi, dan kompos bernutrisi tinggi Desa Lumeneng.">
    <meta name="theme-color" content="#1e4d2b">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/toko/publik.js'])
</head>
<body class="bg-surface text-on-surface min-h-screen flex flex-col antialiased selection:bg-primary-fixed selection:text-on-primary-fixed">

    {{-- TOP APP HEADER --}}
    <header class="fixed top-0 inset-x-0 z-50 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(26,46,31,0.06)] pt-safe border-b border-surface-container-high/60">
        <div class="h-16 px-margin max-w-lg md:max-w-3xl lg:max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-space-sm min-w-0">
                <img alt="Koci Circular Logo"
                     class="h-9 w-auto object-contain flex-shrink-0 rounded-full"
                     src="{{ asset('gambar/toko/koci-logo.png') }}"
                     onerror="this.src='{{ asset('favicon.svg') }}'">
                <div class="flex flex-col min-w-0">
                    <span class="font-label-sm text-label-sm text-secondary tracking-wider uppercase font-semibold">Koci Circular</span>
                    <h1 class="font-headline-sm text-headline-sm text-on-surface truncate leading-tight">Katalog Produk Sederhana</h1>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Tautan cepat ke Rekap Hasil Panen & Penjualan --}}
                <a href="{{ route('toko.hasil') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container text-xs font-bold text-primary-container hover:bg-surface-container-high transition-colors"
                   title="Buka rekap hasil panen dan penjualan">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Rekap Hasil</span>
                </a>

                {{-- Notifikasi --}}
                <button type="button"
                        aria-label="Notifikasi"
                        onclick="tampilkanPesan('Belum ada pemberitahuan baru hari ini.')"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                </button>

                {{-- Profil / Masuk --}}
                <a href="{{ route('admin.beranda') }}"
                   aria-label="Profil Pengguna"
                   class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-on-primary hover:bg-primary-hutan transition-colors">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    {{-- KONTEN UTAMA --}}
    <main class="flex-1 w-full bg-surface pt-20 pb-36 max-w-lg md:max-w-3xl lg:max-w-4xl mx-auto">
        <div class="flex flex-col w-full">

            {{-- SEARCH & QUICK ACTION --}}
            <div class="px-margin pt-space-xs pb-space-sm flex items-center gap-space-sm">
                <div class="relative flex-1">
                    <svg class="size-5 absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input id="input-pencarian"
                           class="w-full h-11 pl-10 pr-4 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-xl outline-none shadow-sm placeholder:text-outline focus:ring-2 focus:ring-primary-container transition-all"
                           placeholder="Cari jamur segar, bibit baglog, kopi..."
                           type="search"
                           autocomplete="off">
                </div>
                <button type="button"
                        aria-label="Filter kategori"
                        onclick="resetSemuaFilter()"
                        class="w-11 h-11 rounded-xl bg-surface-container-lowest flex items-center justify-center text-on-surface-variant shadow-sm active:scale-95 hover:bg-surface-container transition-all"
                        title="Atur ulang filter">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="21" y1="4" x2="14" y2="4"/><line x1="10" y1="4" x2="3" y2="4"/><line x1="21" y1="12" x2="12" y2="12"/><line x1="8" y1="12" x2="3" y2="12"/><line x1="21" y1="20" x2="16" y2="20"/><line x1="12" y1="20" x2="3" y2="20"/><line x1="14" y1="1" x2="14" y2="7"/><line x1="8" y1="9" x2="8" y2="15"/><line x1="16" y1="17" x2="16" y2="23"/>
                    </svg>
                </button>
            </div>

            {{-- SEGMENTED TAB SWITCHER --}}
            <div class="px-margin py-space-xs">
                <div class="bg-surface-container-high p-1 rounded-full flex items-center gap-1 shadow-inner">
                    <button type="button"
                            id="tab-semua"
                            onclick="gantiKategori('semua')"
                            class="flex-1 min-w-0 py-2 px-3 rounded-full font-label-md text-label-md text-center transition-all bg-primary-container text-on-primary shadow-sm flex items-center justify-center gap-1.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <span class="truncate">Semua Produk</span>
                    </button>
                    <button type="button"
                            id="tab-jamur"
                            onclick="gantiKategori('jamur')"
                            class="flex-1 min-w-0 py-2 px-3 rounded-full font-label-md text-label-md text-center transition-all text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3.5 12a8.5 8.5 0 0 1 17 0ZM9.5 12v6a2.5 2.5 0 0 0 5 0v-6" />
                        </svg>
                        <span class="truncate">Jamur Tiram &amp; Olahan</span>
                    </button>
                    <button type="button"
                            id="tab-kopi"
                            onclick="gantiKategori('kopi')"
                            class="flex-1 min-w-0 py-2 px-3 rounded-full font-label-md text-label-md text-center transition-all text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM17 10h1.5a2.5 2.5 0 0 1 0 5H17M8 3v3M12.5 3v3" />
                        </svg>
                        <span class="truncate">Kopi Konservasi</span>
                    </button>
                </div>
            </div>

            {{-- SUB-CATEGORY FILTER CHIPS --}}
            <div class="overflow-x-auto no-scrollbar flex items-center gap-2 px-margin py-space-sm" id="wadah-filter-chips">
                @foreach ($filterChips as $chip)
                    <button type="button"
                            data-chip="{{ $chip }}"
                            onclick="pilihChip('{{ $chip }}')"
                            @class([
                                'chip-tombol flex-shrink-0 px-3.5 py-1.5 rounded-full font-label-sm text-label-sm shadow-sm transition-all',
                                'bg-primary-container text-on-primary' => $loop->first,
                                'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' => ! $loop->first,
                            ])>
                        {{ $chip }}
                    </button>
                @endforeach
            </div>

            {{-- IMPACT BANNER (Petani Lumeneng / Sirkular) --}}
            <div class="px-margin py-space-xs">
                <div class="bg-surface-container-lowest rounded-xl p-3.5 shadow-sm border border-surface-container-high/50 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container flex-shrink-0">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-label-md text-label-md text-on-surface font-bold truncate">Hasil Panen Petani Desa Lumeneng</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant truncate">100% media tanam limbah bubuk &amp; cangkang kopi sirkular</p>
                    </div>
                    <span class="font-label-sm text-label-sm text-primary-container bg-surface-container px-2.5 py-1 rounded-full font-bold">
                        Sirkular
                    </span>
                </div>
            </div>

            {{-- PRODUCT GRID (2-Kolom Mobile, 4-Kolom Desktop) --}}
            <div class="px-margin pt-space-sm pb-space-lg grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4" id="daftar-kartu-produk">
                @foreach ($produk as $p)
                    <div class="kartu-produk bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high/60 overflow-hidden flex flex-col justify-between group transition-all hover:shadow-md"
                         data-id="{{ $p['id'] }}"
                         data-kategori="{{ $p['kategori'] }}"
                         data-subkategori="{{ $p['subkategori'] }}"
                         data-nama="{{ strtolower($p['nama']) }}"
                         data-harga="{{ $p['harga'] }}">
                        <div>
                            {{-- Foto & Lencana Status Stok --}}
                            <div class="relative w-full aspect-square bg-surface-container-low overflow-hidden">
                                <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     src="{{ asset($p['gambar']) }}"
                                     alt="{{ $p['nama'] }}"
                                     loading="lazy">
                                <div class="absolute top-2 left-2 {{ $p['stok_status'] === 'terbatas' ? 'bg-secondary-container/90 text-on-secondary-container' : 'bg-surface-container-lowest/90 text-primary-container' }} backdrop-blur-sm px-2 py-0.5 rounded-full flex items-center gap-1 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $p['stok_status'] === 'terbatas' ? 'bg-secondary' : 'bg-primary-container' }}"></span>
                                    <span class="font-label-sm text-[11px] font-semibold">{{ $p['stok_teks'] }}</span>
                                </div>
                            </div>

                            {{-- Informasi Produk --}}
                            <div class="p-3">
                                <span class="font-label-sm text-[11px] text-secondary font-semibold uppercase tracking-wide">{{ $p['label'] }}</span>
                                <h2 class="font-headline-sm text-[15px] leading-tight text-on-surface font-semibold line-clamp-1 mt-0.5">
                                    {{ $p['nama'] }}
                                </h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                                    {{ $p['takaran'] }}
                                </p>
                            </div>
                        </div>

                        {{-- Harga & Tombol Tambah --}}
                        <div class="px-3 pb-3 pt-1 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[11px] text-outline">Harga</span>
                                <span class="font-label-lg text-sm sm:text-base font-bold text-primary-container">
                                    Rp {{ number_format($p['harga'], 0, ',', '.') }}
                                </span>
                            </div>
                            <button type="button"
                                    aria-label="Tambah {{ $p['nama'] }} ke keranjang"
                                    onclick="tambahKeKeranjang({{ $p['id'] }}, '{{ addslashes($p['nama']) }}', {{ $p['harga'] }})"
                                    class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center shadow-md active:scale-90 hover:bg-primary-hutan transition-transform">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- KEADAAN KOSONG PENCARIAN --}}
            <div id="pesan-kosong" class="hidden px-margin py-8 text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant mb-2">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <p class="font-bold text-on-surface">Produk tidak ditemukan</p>
                <p class="text-sm text-on-surface-variant mt-1">Coba kata kunci lain atau pilih Semua Kategori.</p>
                <button type="button" onclick="resetSemuaFilter()" class="mt-3 px-4 py-2 rounded-xl bg-primary-container text-on-primary text-xs font-bold shadow-sm">
                    Tampilkan Semua Produk
                </button>
            </div>

            {{-- QUALITY ASSURANCE CALLOUT --}}
            <div class="px-margin mb-space-lg">
                <div class="p-4 rounded-xl bg-surface-container-high border border-surface-container-highest flex items-start gap-3">
                    <div class="w-6 h-6 text-primary-container flex-shrink-0 mt-0.5">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="font-label-md text-label-md font-bold text-on-surface">Jaminan Kesegaran &amp; Ketelusuran</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 leading-relaxed">
                            Setiap pembelian langsung memberdayakan para pembudidaya jamur tiram dan pekebun kopi sirkular di lingkar Desa Lumeneng.
                        </p>
                    </div>
                </div>
            </div>

            {{-- BANNER KE REKAP DATA PANEN & PENJUALAN --}}
            <div class="px-margin mb-6">
                <div class="p-4 rounded-xl bg-surface-container-lowest border border-surface-container-high shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-container/10 text-primary-container">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </span>
                        <div>
                            <p class="font-bold text-sm text-on-surface">Transparansi Hasil Panen Desa</p>
                            <p class="text-xs text-on-surface-variant">Catatan lengkap panen kopi, olahan cangkang, dan hasil penjualan.</p>
                        </div>
                    </div>
                    <a href="{{ route('toko.hasil') }}"
                       class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-surface-container px-4 text-xs font-bold text-primary-container hover:bg-surface-container-high transition-colors">
                        <span>Lihat Rekap Data Panen</span>
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

        </div>
    </main>

    {{-- FLOATING SUMMARY CART BAR (DI ATAS BOTTOM NAV) --}}
    <div class="fixed bottom-24 inset-x-0 z-40 px-margin pointer-events-none">
        <div class="max-w-md mx-auto pointer-events-auto bg-primary-container text-on-primary rounded-2xl p-3 shadow-xl flex items-center justify-between gap-3 border border-primary-fixed/20 transition-all" id="batang-keranjang">
            <div class="flex items-center gap-3 pl-1 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-surface-container-lowest/15 flex items-center justify-center text-on-primary flex-shrink-0 relative">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <span id="cart-count-badge"
                          class="absolute -top-1 -right-1 min-w-4 h-4 px-1 bg-secondary-fixed text-on-secondary-fixed text-[10px] font-bold rounded-full flex items-center justify-center shadow-sm transition-transform duration-200">
                        0
                    </span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span id="cart-item-label" class="font-label-sm text-label-sm text-on-primary-container truncate font-normal">
                        Keranjang Kosong
                    </span>
                    <span id="cart-total-label" class="font-label-lg text-label-lg font-bold text-on-primary truncate">
                        Rp 0
                    </span>
                </div>
            </div>
            <button type="button"
                    onclick="bukaModalKeranjang()"
                    class="bg-surface-container-lowest text-primary-container px-4 py-2 rounded-xl font-label-md text-label-md font-semibold flex items-center gap-1.5 shadow-sm active:scale-95 hover:bg-surface-container-low transition-transform flex-shrink-0">
                <span>Lihat Keranjang</span>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- MODAL / DRAWER KERANJANG BELANJA --}}
    <div id="modal-keranjang" class="fixed inset-0 z-[1200] hidden bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 transition-opacity">
        <div class="w-full max-w-lg bg-surface-container-lowest rounded-t-3xl sm:rounded-2xl p-5 shadow-2xl max-h-[85vh] flex flex-col animate-in fade-in slide-in-from-bottom duration-200">
            <div class="flex items-center justify-between border-b border-surface-container-high pb-3">
                <div class="flex items-center gap-2">
                    <div class="size-8 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-on-surface">Keranjang Pesanan Sirkular</h3>
                </div>
                <button type="button" onclick="tutupModalKeranjang()" class="p-1 rounded-full text-on-surface-variant hover:bg-surface-container">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div id="daftar-item-keranjang" class="flex-1 overflow-y-auto py-3 space-y-3 divide-y divide-surface-container">
                {{-- Diisi secara dinamis via JavaScript --}}
            </div>

            <div class="border-t border-surface-container-high pt-4 space-y-3">
                <div class="flex justify-between items-center text-base">
                    <span class="font-normal text-on-surface-variant">Total Pembelian</span>
                    <strong id="modal-total-harga" class="text-xl font-bold text-primary-container">Rp 0</strong>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="button"
                            id="tombol-pesan-wa"
                            onclick="pesanLewatWhatsApp()"
                            class="w-full py-3 px-4 rounded-xl bg-primary-container text-on-primary font-bold flex items-center justify-center gap-2 shadow-md hover:bg-primary-hutan active:scale-98 transition-all">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span>Pesan via WhatsApp Petani</span>
                    </button>
                    <button type="button"
                            onclick="kosongkanKeranjang()"
                            class="w-full py-2 text-center text-xs text-outline hover:text-error transition-colors">
                        Kosongkan Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- BOTTOM NAVIGATION BAR --}}
    <nav class="fixed bottom-0 inset-x-0 z-50 pb-safe bg-surface-container-lowest/95 backdrop-blur-xl shadow-[0_-4px_20px_rgba(26,46,31,0.06)] border-t border-surface-container-high/60"
         data-active-classes="text-primary-container font-semibold">
        <div class="flex items-center justify-between h-20 px-space-xs max-w-lg md:max-w-xl mx-auto relative">
            {{-- 1. Beranda --}}
            <a href="{{ route('landing') }}"
               class="flex-1 flex flex-col items-center justify-center py-1 gap-0.5 text-on-surface-variant hover:text-on-surface transition-colors min-h-[48px]">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span class="font-label-sm text-label-sm">Beranda</span>
            </a>

            {{-- 2. Edukasi --}}
            <a href="{{ \Illuminate\Support\Facades\Route::has('edukasi.index') ? route('edukasi.index') : route('landing') }}"
               class="flex-1 flex flex-col items-center justify-center py-1 gap-0.5 text-on-surface-variant hover:text-on-surface transition-colors min-h-[48px]">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
                </svg>
                <span class="font-label-sm text-label-sm">Edukasi</span>
            </a>

            {{-- 3. Catat (Elevated Center FAB) --}}
            <div class="flex-1 flex flex-col items-center justify-center relative -top-3.5">
                <a href="{{ route('admin.toko.panen-kopi.index') }}"
                   class="w-14 h-14 rounded-full bg-primary-container text-on-primary flex items-center justify-center shadow-[0_8px_16px_-4px_rgba(30,77,43,0.35)] active:scale-95 hover:bg-primary-hutan transition-transform"
                   title="Input dan Pencatatan Panen/Limbah">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/><path d="m14 16 3 3 3-3"/><path d="M8.293 13.596 5.2 8.27A1.785 1.785 0 0 1 5.2 6.49 1.83 1.83 0 0 1 6.766 5.6h5.434"/><path d="m16 9-3-3 3-3"/><path d="m9.3 5.4 1.2-2.1"/>
                    </svg>
                </a>
                <span class="font-label-sm text-label-sm text-on-surface-variant mt-1 font-semibold">Catat</span>
            </div>

            {{-- 4. Katalog (Aktif) --}}
            <a href="{{ route('toko.index') }}"
               aria-current="page"
               class="flex-1 flex flex-col items-center justify-center py-1 gap-0.5 min-h-[48px] text-primary-container font-semibold">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <span class="font-label-sm text-label-sm">Katalog</span>
            </a>

            {{-- 5. Profil --}}
            <a href="{{ route('admin.beranda') }}"
               class="flex-1 flex flex-col items-center justify-center py-1 gap-0.5 text-on-surface-variant hover:text-on-surface transition-colors min-h-[48px]">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                </svg>
                <span class="font-label-sm text-label-sm">Profil</span>
            </a>
        </div>
    </nav>

    {{-- TOAST NOTIFIKASI SEDERHANA --}}
    <div id="toast-notif" class="fixed top-20 inset-x-0 z-[1100] flex justify-center pointer-events-none px-4 transition-all duration-300 opacity-0 -translate-y-2">
        <div class="bg-on-surface text-surface-container-lowest px-4 py-2.5 rounded-full shadow-lg text-xs font-semibold flex items-center gap-2">
            <span id="toast-pesan">Produk ditambahkan ke keranjang</span>
        </div>
    </div>

    {{-- LOGIKA INTERAKTIF (Search, Filter, Tab Switch, Keranjang, WhatsApp) --}}
    <script>
        // Data Produk dari Blade
        const produkSemua = @json($produk);

        // State Aplikasi
        let keranjang = {};
        let kategoriAktif = 'semua';
        let chipAktif = 'Semua';
        let kataKunci = '';

        // Elemen DOM
        const inputPencarian = document.getElementById('input-pencarian');
        const daftarKartu = document.querySelectorAll('.kartu-produk');
        const pesanKosong = document.getElementById('pesan-kosong');
        const badgeCount = document.getElementById('cart-count-badge');
        const itemLabel = document.getElementById('cart-item-label');
        const totalLabel = document.getElementById('cart-total-label');
        const modalKeranjang = document.getElementById('modal-keranjang');
        const daftarItemKeranjang = document.getElementById('daftar-item-keranjang');
        const modalTotalHarga = document.getElementById('modal-total-harga');

        // Initial 2 item default demo sesuai stitch ai (Jamur Tiram 14.000 + Baglog Jamur 6.500 = 20.500)
        keranjang = {
            1: { id: 1, nama: 'Jamur Tiram Putih', harga: 14000, jumlah: 1 },
            2: { id: 2, nama: 'Baglog Jamur Koci', harga: 6500, jumlah: 1 }
        };
        perbaruiTampilanKeranjang(false);

        // Pencarian Teks
        if (inputPencarian) {
            inputPencarian.addEventListener('input', (e) => {
                kataKunci = e.target.value.toLowerCase().trim();
                saringProduk();
            });
        }

        // Ganti Kategori Tab (Semua, Jamur, Kopi)
        function gantiKategori(kat) {
            kategoriAktif = kat;
            const tabSemua = document.getElementById('tab-semua');
            const tabJamur = document.getElementById('tab-jamur');
            const tabKopi = document.getElementById('tab-kopi');

            const kelasAktif = 'flex-1 min-w-0 py-2 px-3 rounded-full font-label-md text-label-md text-center transition-all bg-primary-container text-on-primary shadow-sm flex items-center justify-center gap-1.5';
            const kelasTidakAktif = 'flex-1 min-w-0 py-2 px-3 rounded-full font-label-md text-label-md text-center transition-all text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5';

            if (tabSemua) tabSemua.className = kat === 'semua' ? kelasAktif : kelasTidakAktif;
            if (tabJamur) tabJamur.className = kat === 'jamur' ? kelasAktif : kelasTidakAktif;
            if (tabKopi) tabKopi.className = kat === 'kopi' ? kelasAktif : kelasTidakAktif;

            saringProduk();
        }

        // Filter Chip Subkategori
        function pilihChip(chip) {
            chipAktif = chip;
            document.querySelectorAll('.chip-tombol').forEach(btn => {
                if (btn.getAttribute('data-chip') === chip) {
                    btn.className = 'chip-tombol flex-shrink-0 px-3.5 py-1.5 rounded-full font-label-sm text-label-sm shadow-sm transition-all bg-primary-container text-on-primary';
                } else {
                    btn.className = 'chip-tombol flex-shrink-0 px-3.5 py-1.5 rounded-full font-label-sm text-label-sm shadow-sm transition-all bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container';
                }
            });
            saringProduk();
        }

        // Logika Penyaringan Gabungan (Tab + Chip + Teks)
        function saringProduk() {
            let adaYangTampil = 0;

            daftarKartu.forEach(kartu => {
                const kat = kartu.getAttribute('data-kategori');
                const subkat = kartu.getAttribute('data-subkategori');
                const nama = kartu.getAttribute('data-nama');

                const cocokKategori = (kategoriAktif === 'semua') || (kat === kategoriAktif);
                const cocokChip = (chipAktif === 'Semua') || (subkat === chipAktif);
                const cocokTeks = !kataKunci || nama.includes(kataKunci);

                if (cocokKategori && cocokChip && cocokTeks) {
                    kartu.style.display = '';
                    adaYangTampil++;
                } else {
                    kartu.style.display = 'none';
                }
            });

            if (pesanKosong) {
                pesanKosong.classList.toggle('hidden', adaYangTampil > 0);
            }
        }

        // Atur Ulang Semua Filter
        function resetSemuaFilter() {
            if (inputPencarian) inputPencarian.value = '';
            kataKunci = '';
            gantiKategori('semua');
            pilihChip('Semua');
        }

        // Tambah ke Keranjang
        function tambahKeKeranjang(id, nama, harga) {
            if (!keranjang[id]) {
                keranjang[id] = { id, nama, harga, jumlah: 0 };
            }
            keranjang[id].jumlah += 1;
            perbaruiTampilanKeranjang(true);
            tampilkanPesan(nama + ' ditambahkan ke keranjang');
        }

        // Ubah Jumlah Item di Keranjang
        function ubahJumlah(id, delta) {
            if (!keranjang[id]) return;
            keranjang[id].jumlah += delta;
            if (keranjang[id].jumlah <= 0) {
                delete keranjang[id];
            }
            perbaruiTampilanKeranjang(false);
            gambarDaftarModal();
        }

        function kosongkanKeranjang() {
            keranjang = {};
            perbaruiTampilanKeranjang(false);
            tutupModalKeranjang();
            tampilkanPesan('Keranjang dikosongkan');
        }

        // Perbarui UI Bar Keranjang Bawah
        function perbaruiTampilanKeranjang(animasi) {
            let totalItem = 0;
            let totalHarga = 0;

            for (const id in keranjang) {
                totalItem += keranjang[id].jumlah;
                totalHarga += keranjang[id].jumlah * keranjang[id].harga;
            }

            if (badgeCount) badgeCount.textContent = totalItem;
            if (itemLabel) {
                itemLabel.textContent = totalItem > 0 ? (totalItem + ' Produk Terpilih') : 'Keranjang Kosong';
            }
            if (totalLabel) {
                totalLabel.textContent = 'Rp ' + totalHarga.toLocaleString('id-ID');
            }

            if (animasi && badgeCount) {
                badgeCount.classList.add('scale-150');
                setTimeout(() => badgeCount.classList.remove('scale-150'), 250);
            }
        }

        // Buka / Tutup Modal Keranjang
        function bukaModalKeranjang() {
            gambarDaftarModal();
            if (modalKeranjang) modalKeranjang.classList.remove('hidden');
        }

        function tutupModalKeranjang() {
            if (modalKeranjang) modalKeranjang.classList.add('hidden');
        }

        // Render Isi Modal Keranjang
        function gambarDaftarModal() {
            if (!daftarItemKeranjang) return;
            daftarItemKeranjang.innerHTML = '';

            const keys = Object.keys(keranjang);
            let totalHarga = 0;

            if (keys.length === 0) {
                daftarItemKeranjang.innerHTML = '<p class="text-center py-6 text-sm text-outline">Belum ada produk di dalam keranjang.</p>';
                if (modalTotalHarga) modalTotalHarga.textContent = 'Rp 0';
                return;
            }

            keys.forEach(id => {
                const item = keranjang[id];
                const subtotal = item.jumlah * item.harga;
                totalHarga += subtotal;

                const baris = document.createElement('div');
                baris.className = 'pt-2.5 first:pt-0 flex items-center justify-between gap-3';
                baris.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-on-surface truncate">${item.nama}</p>
                        <p class="text-xs text-on-surface-variant">Rp ${item.harga.toLocaleString('id-ID')} × ${item.jumlah}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="ubahJumlah(${item.id}, -1)" class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center font-bold text-on-surface active:scale-90">-</button>
                        <span class="w-6 text-center text-sm font-bold">${item.jumlah}</span>
                        <button type="button" onclick="ubahJumlah(${item.id}, 1)" class="w-7 h-7 rounded-lg bg-primary-container text-on-primary flex items-center justify-center font-bold active:scale-90">+</button>
                    </div>
                    <span class="text-xs font-bold text-primary-container w-20 text-right">Rp ${subtotal.toLocaleString('id-ID')}</span>
                `;
                daftarItemKeranjang.appendChild(baris);
            });

            if (modalTotalHarga) {
                modalTotalHarga.textContent = 'Rp ' + totalHarga.toLocaleString('id-ID');
            }
        }

        // Pesan via WhatsApp
        function pesanLewatWhatsApp() {
            const keys = Object.keys(keranjang);
            if (keys.length === 0) {
                alert('Keranjang belanja Anda masih kosong. Silakan pilih produk terlebih dahulu.');
                return;
            }

            let teks = 'Halo Pengelola Toko Desa Lumeneng, saya ingin memesan produk sirkular berikut:\n\n';
            let totalHarga = 0;
            keys.forEach(id => {
                const item = keranjang[id];
                const subtotal = item.jumlah * item.harga;
                totalHarga += subtotal;
                teks += `• ${item.nama} (${item.jumlah} pcs) - Rp ${subtotal.toLocaleString('id-ID')}\n`;
            });
            teks += `\n*Total Pesanan: Rp ${totalHarga.toLocaleString('id-ID')}*`;
            teks += '\n\nMohon informasi ketersediaan dan proses pengambilannya. Terima kasih!';

            const noHp = '6281234567890'; // Kontak UMKM / Bumdes Lumeneng
            const url = `https://wa.me/${noHp}?text=${encodeURIComponent(teks)}`;
            window.open(url, '_blank');
        }

        // Toast Notifikasi Sederhana
        let toastTimer = null;
        function tampilkanPesan(pesan) {
            const toast = document.getElementById('toast-notif');
            const teks = document.getElementById('toast-pesan');
            if (!toast || !teks) return;

            teks.textContent = pesan;
            toast.classList.remove('opacity-0', '-translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');

            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', '-translate-y-2');
            }, 2000);
        }

        // Tutup modal saat klik luar
        if (modalKeranjang) {
            modalKeranjang.addEventListener('click', (e) => {
                if (e.target === modalKeranjang) tutupModalKeranjang();
            });
        }
    </script>
</body>
</html>
