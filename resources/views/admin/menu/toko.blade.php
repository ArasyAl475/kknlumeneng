@php
    // Etalase produk (dropdown). Daftar pola dipakai juga untuk memutuskan dropdown terbuka atau tidak.
    $menuEtalase = [
        [['admin.toko.beranda', 'admin.toko.marketplace.*'], 'admin.toko.beranda', 'Marketplace', 'penjualan'],
        [['admin.toko.produk.*', 'admin.toko.foto.*'], 'admin.toko.produk.index', 'Semua produk', 'produk'],
        [['admin.toko.pengaturan.*'], 'admin.toko.pengaturan.edit', 'Info penjual', 'profil'],
        [['admin.toko.aktivitas'], 'admin.toko.aktivitas', 'Log aktivitas', 'log'],
    ];
    $sedangDiEtalase = request()->routeIs(...collect($menuEtalase)->pluck(0)->flatten()->all());

    // Pencatatan hasil panen yang tampil di halaman /toko/hasil.
    $menuPanen = [
        ['admin.toko.panen-kopi.*', 'admin.toko.panen-kopi.index', 'Panen kopi', 'kopi'],
        ['admin.toko.panen-jamur.*', 'admin.toko.panen-jamur.index', 'Panen jamur', 'jamur'],
        ['admin.toko.penjualan-jamur.*', 'admin.toko.penjualan-jamur.index', 'Penjualan jamur', 'penjualan'],
    ];
@endphp
{{-- <details> dipakai agar dropdown tetap jalan tanpa JavaScript; terbuka sendiri saat admin sedang di halaman etalase. --}}
<details data-menu="toko" class="group mt-5" @if ($sedangDiEtalase) open @endif>
    <summary class="menu-admin__item font-bold" style="cursor: pointer">
        @include('layouts.partials.ikon-menu', ['nama' => 'penjualan'])
        <span class="menu-admin__label">Toko Desa</span>
        <svg class="size-5 shrink-0 motion-safe:transition-transform group-open:rotate-180" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <ul class="mt-1 ml-5 space-y-0.5 border-l border-white/15 pl-2">
        @foreach ($menuEtalase as [$pola, $rute, $label, $ikon])
            @include('layouts.partials.item-menu-admin', ['pola' => $pola, 'rute' => $rute, 'label' => $label, 'ikon' => $ikon])
        @endforeach
    </ul>
</details>

<p class="menu-admin__judul">Hasil Panen</p>
<ul class="space-y-0.5">
    @foreach ($menuPanen as [$pola, $rute, $label, $ikon])
        @include('layouts.partials.item-menu-admin', ['pola' => $pola, 'rute' => $rute, 'label' => $label, 'ikon' => $ikon])
    @endforeach
</ul>
