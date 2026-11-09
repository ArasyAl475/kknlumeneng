{{-- Menu Desain Web: satu butir (bukan dropdown) karena hanya punya satu halaman. --}}
@include('layouts.partials.item-menu-admin', ['pola' => 'admin.desain.*', 'rute' => 'admin.desain.index', 'label' => 'Desain Web', 'ikon' => 'desain'])
