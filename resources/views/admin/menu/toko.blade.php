@php
    $menuToko = [
        ['admin.toko.panen-kopi.*', 'admin.toko.panen-kopi.index', 'Panen kopi', 'kopi'],
        ['admin.toko.panen-jamur.*', 'admin.toko.panen-jamur.index', 'Panen jamur', 'jamur'],
        ['admin.toko.penjualan-jamur.*', 'admin.toko.penjualan-jamur.index', 'Penjualan jamur', 'penjualan'],
    ];
@endphp
<p class="menu-admin__judul">Toko Desa</p>
<ul class="space-y-0.5">
    @foreach ($menuToko as [$pola, $rute, $label, $ikon])
        @include('layouts.partials.item-menu-admin', ['pola' => $pola, 'rute' => $rute, 'label' => $label, 'ikon' => $ikon])
    @endforeach
</ul>
