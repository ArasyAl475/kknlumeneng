@php
    $menuEws = [
        ['admin.ews.validasi.*', 'admin.ews.validasi.index', 'Validasi dan Awas', 'validasi', true],
        ['admin.ews.alat', 'admin.ews.alat', 'Status alat', 'alat', false],
        ['admin.ews.wilayah.*', 'admin.ews.wilayah.index', 'Daftar wilayah', 'wilayah', false],
        ['admin.ews.ambang.*', 'admin.ews.ambang.edit', 'Ambang batas sensor', 'ambang', false],
        ['admin.ews.riwayat.*', 'admin.ews.riwayat.index', 'Riwayat pembacaan', 'riwayat', false],
        ['admin.ews.aktivitas', 'admin.ews.aktivitas', 'Log aktivitas', 'log', false],
    ];
    if (Route::has('admin.ews.simulator.index')) {
        $menuEws[] = ['admin.ews.simulator.*', 'admin.ews.simulator.index', 'Simulator', 'simulator', false];
    }
    $jumlahMenunggu = once(fn () => \App\Models\Ews\Pembacaan::query()->where('validasi', \App\Models\Ews\Pembacaan::VALIDASI_MENUNGGU)->count());
@endphp
<p class="menu-admin__judul">Peringatan Dini</p>
<ul class="space-y-0.5">
    @foreach ($menuEws as [$pola, $rute, $label, $ikon, $tampilJumlah])
        @include('layouts.partials.item-menu-admin', ['pola' => $pola, 'rute' => $rute, 'label' => $label, 'ikon' => $ikon, 'lencana' => $tampilJumlah && $jumlahMenunggu > 0 ? $jumlahMenunggu : null])
    @endforeach
</ul>
