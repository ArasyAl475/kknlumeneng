{{-- Navbar halaman publik. Dipakai layouts/app dan toko/kerangka agar menunya selalu sama.
     Wajib berada di bawah <body class="tema-hijau"> (warna) dan setelah <x-ikon-sprite /> (ikon). --}}
@php $menu = \App\Support\Modul::tersedia(); @endphp

<header data-navbar-publik class="sticky top-0 z-[1000] bg-kopi shadow-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-x-4 px-4 py-0">

        {{-- Logo & Nama Desa --}}
        <a href="{{ route('landing') }}" class="flex items-center gap-3 py-3 focus-visible:outline-white">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-white">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12 L12 3 L21 12 V21 H15 V15 H9 V21 H3 Z"/>
                </svg>
            </span>
            <span class="leading-tight">
                <span class="block text-xs font-normal text-white/70">Portal Resmi</span>
                <span class="block font-bold text-white">Desa Lumeneng</span>
            </span>
        </a>

        {{-- Nav Desktop --}}
        @if ($menu)
            <nav aria-label="Menu utama" class="hidden items-center gap-1 lg:flex">
                @foreach ($menu as $modul)
                    <a href="{{ route($modul['rute']) }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-white {{ request()->routeIs($modul['rute']) ? 'bg-white/20 text-white' : '' }}">
                        <x-ikon :nama="$modul['ikon']" class="size-4" />
                        {{ $modul['nama'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Mobile Menu --}}
            <details class="lg:hidden" id="detail-menu">
                <summary class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 font-bold text-white focus-visible:outline-white">
                    <x-ikon nama="menu" class="size-5" />
                    <span class="text-sm">Menu</span>
                </summary>
                <nav aria-label="Menu utama"
                     class="absolute inset-x-0 z-[1000] border-t border-white/10 bg-kopi-tua px-4 py-3 shadow-xl">
                    @foreach ($menu as $modul)
                        <a href="{{ route($modul['rute']) }}"
                           class="flex min-h-12 items-center gap-3 rounded-lg px-3 py-2 font-bold text-white hover:bg-white/10 focus-visible:outline-white">
                            <x-ikon :nama="$modul['ikon']" class="size-5" />
                            {{ $modul['nama'] }}
                        </a>
                    @endforeach
                </nav>
            </details>
        @endif
    </div>
</header>

{{-- Tutup menu HP setelah salah satu tautan diketuk --}}
<script>
    document.querySelectorAll('#detail-menu nav a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('detail-menu').removeAttribute('open');
        });
    });
</script>
