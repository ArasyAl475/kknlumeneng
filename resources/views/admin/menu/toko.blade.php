@php
    $menuToko = [
        [['admin.toko.beranda', 'admin.toko.marketplace.*'], 'admin.toko.beranda', 'Marketplace'],
        [['admin.toko.produk.*'], 'admin.toko.produk.index', 'Semua produk'],
        [['admin.toko.pengaturan.*'], 'admin.toko.pengaturan.edit', 'Info penjual'],
        [['admin.toko.aktivitas'], 'admin.toko.aktivitas', 'Log aktivitas'],
    ];
@endphp
<p class="mt-4 px-2 text-sm font-bold">Toko Desa</p>
<ul class="mt-1 space-y-1">
    @foreach ($menuToko as [$pola, $rute, $label])
        <li>
            <a href="{{ route($rute) }}" @class(['flex min-h-11 items-center rounded px-2 hover:bg-kopi focus-visible:outline-white', 'bg-kopi font-bold' => request()->routeIs(...$pola)]) @if (request()->routeIs(...$pola)) aria-current="page" @endif>
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>
