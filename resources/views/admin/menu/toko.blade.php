@php
    $menuToko = [
        [['admin.toko.beranda', 'admin.toko.marketplace.*'], 'admin.toko.beranda', 'Marketplace'],
        [['admin.toko.produk.*'], 'admin.toko.produk.index', 'Semua produk'],
        [['admin.toko.pengaturan.*'], 'admin.toko.pengaturan.edit', 'Info penjual'],
        [['admin.toko.aktivitas'], 'admin.toko.aktivitas', 'Log aktivitas'],
    ];
    $sedangDiToko = request()->routeIs('admin.toko.*');
@endphp
{{-- <details> dipakai agar dropdown tetap jalan tanpa JavaScript; terbuka sendiri saat admin sedang di halaman Toko. --}}
<details data-menu="toko" class="group mt-4" @if ($sedangDiToko) open @endif>
    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 rounded px-2 font-bold hover:bg-kopi focus-visible:outline-white [&::-webkit-details-marker]:hidden">
        Toko Desa
        <svg class="size-5 shrink-0 motion-safe:transition-transform group-open:rotate-180" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <ul class="mt-1 space-y-1 ml-2 border-l border-white/30 pl-2">
        @foreach ($menuToko as [$pola, $rute, $label])
            <li>
                <a href="{{ route($rute) }}" @class(['flex min-h-11 items-center rounded px-2 hover:bg-kopi focus-visible:outline-white', 'bg-kopi font-bold' => request()->routeIs(...$pola)]) @if (request()->routeIs(...$pola)) aria-current="page" @endif>
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</details>
