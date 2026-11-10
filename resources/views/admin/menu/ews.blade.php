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
{{-- Jumlah data menunggu validasi ikut tampil di judul dropdown, agar terlihat walau dropdown tertutup. --}}
@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'ews',
    'judul' => 'Peringatan Dini',
    'ikon' => 'peringatan',
    'butir' => collect($menuEws)
        ->map(fn (array $m) => [$m[0], $m[1], $m[2], $m[3], $m[4] && $jumlahMenunggu > 0 ? $jumlahMenunggu : null])
        ->all(),
])
