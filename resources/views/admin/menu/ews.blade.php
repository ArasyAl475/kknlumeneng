@php
    $menuEws = [
        ['admin.ews.validasi.*', 'admin.ews.validasi.index', 'Validasi dan Awas', true],
        ['admin.ews.alat', 'admin.ews.alat', 'Status alat', false],
        ['admin.ews.wilayah.*', 'admin.ews.wilayah.index', 'Daftar wilayah', false],
        ['admin.ews.ambang.*', 'admin.ews.ambang.edit', 'Ambang batas sensor', false],
        ['admin.ews.riwayat.*', 'admin.ews.riwayat.index', 'Riwayat pembacaan', false],
        ['admin.ews.aktivitas', 'admin.ews.aktivitas', 'Log aktivitas', false],
    ];
    if (Route::has('admin.ews.simulator.index')) {
        $menuEws[] = ['admin.ews.simulator.*', 'admin.ews.simulator.index', 'Simulator', false];
    }
    $jumlahMenunggu = once(fn () => \App\Models\Ews\Pembacaan::query()->where('validasi', \App\Models\Ews\Pembacaan::VALIDASI_MENUNGGU)->count());
@endphp
<p class="mt-4 px-2 text-sm font-bold">Peringatan Dini</p>
<ul class="mt-1 space-y-1">
    @foreach ($menuEws as [$pola, $rute, $label, $tampilJumlah])
        <li>
            <a href="{{ route($rute) }}" @class(['flex min-h-11 items-center justify-between gap-2 rounded px-2 hover:bg-kopi focus-visible:outline-white', 'bg-kopi font-bold' => request()->routeIs($pola)]) @if (request()->routeIs($pola)) aria-current="page" @endif>
                {{ $label }}
                @if ($tampilJumlah && $jumlahMenunggu > 0)
                    <span class="rounded bg-waspada px-2 text-sm font-bold text-tinta">{{ $jumlahMenunggu }}<span class="sr-only"> menunggu</span></span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
