@extends('layouts.app')
@section('judul', 'Hasil panen dan penjualan')
@section('deskripsi', 'Total panen kopi, limbah kopi, panen jamur, dan penjualan jamur Desa Lumeneng.')

@php
    use App\Services\Toko\FormatHasil;
    use App\Services\Toko\RekapHasil;

    $kartu = 'rounded-2xl border border-garis-halus bg-permukaan shadow-sm';
    $lencanaIkon = 'flex size-11 items-center justify-center rounded-xl';
    $tabRincian = [
        ['jenis', 'Jenis', 'Per jenis kopi', $kopi['per_jenis']],
        ['tempat', 'Tempat', 'Per tempat panen', $kopi['per_tempat']],
        ['tahun', 'Tahun', 'Per tahun', $kopi['per_tahun']],
        ['bulan', 'Bulan', 'Per bulan', $kopi['per_bulan']],
        ['hari', 'Hari', 'Per hari panen', $kopi['per_hari']],
    ];
@endphp

@push('kepala')
    <noscript><style>[data-panel-rincian] { display: block !important; } [role='tablist'] { display: none; }</style></noscript>
@endpush
@push('skrip')
    @vite('resources/js/toko/hasil.js')
@endpush

@section('isi')
    <header class="mb-6">
        <p class="text-sm font-bold tracking-wide text-kopi uppercase">Toko Desa</p>
        <h1 class="mt-1 text-[clamp(1.75rem,5vw,2.5rem)] leading-tight font-bold text-balance">Hasil panen dan penjualan</h1>
        <p class="mt-2 max-w-2xl text-redup">Angka total dari catatan admin desa, mulai dari kebun kopi sampai jamur yang terjual.</p>
    </header>

    <section aria-label="Pilihan data" class="mb-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($periode['pilihan'] as $p)
                <a href="{{ route('toko.hasil', $p['kueri']) }}" @if ($p['aktif']) aria-current="true" @endif @class([
                    'inline-flex min-h-11 flex-1 items-center justify-center rounded-full border px-3 font-bold whitespace-nowrap transition-colors sm:flex-none sm:px-4',
                    'border-kopi bg-kopi text-white' => $p['aktif'],
                    'border-garis bg-permukaan hover:bg-kosong-lembut' => ! $p['aktif'],
                ])>{{ $p['label'] }}</a>
            @endforeach
        </div>

        <details class="group {{ $kartu }} mt-3" @if ($periode['lanjutan']) open @endif>
            <summary class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-4 py-2 font-bold">
                Pilih tanggal, jenis, atau tempat
                @include('toko.partials.ikon', ['nama' => 'panah', 'kelas' => 'size-5 transition-transform group-open:rotate-180'])
            </summary>
            <form method="GET" action="{{ route('toko.hasil') }}" class="grid gap-3 border-t border-garis-halus p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-start">
                <x-bidang nama="dari" label="Dari tanggal" type="date" :value="$filter['dari'] ?? null" />
                <x-bidang nama="sampai" label="Sampai tanggal" type="date" :value="$filter['sampai'] ?? null" />
                <div>
                    <label for="jenis" class="mb-1 block font-bold">Jenis kopi</label>
                    <select id="jenis" name="jenis" class="isian">
                        <option value="">Semua jenis</option>
                        @foreach ($pilihan['jenis'] as $jenis)
                            <option value="{{ $jenis }}" @selected(($filter['jenis'] ?? null) === $jenis)>{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tempat" class="mb-1 block font-bold">Tempat panen kopi</label>
                    <select id="tempat" name="tempat" class="isian">
                        <option value="">Semua tempat</option>
                        @foreach ($pilihan['tempat'] as $tempat)
                            <option value="{{ $tempat }}" @selected(($filter['tempat'] ?? null) === $tempat)>{{ $tempat }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-sm text-redup sm:col-span-2 lg:col-span-4">Jenis dan tempat hanya berlaku untuk data kopi. Tanggal berlaku untuk semua data.</p>
                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
                    <x-tombol>Tampilkan</x-tombol>
                    <x-tombol varian="kedua" :href="route('toko.hasil')">Hapus pilihan</x-tombol>
                </div>
            </form>
        </details>

        <p class="mt-3 text-sm text-redup">Menampilkan: <strong class="text-tinta">{{ $periode['keterangan'] }}</strong></p>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Panen kopi adalah hulu seluruh rantai (limbahnya menjadi media jamur), jadi angkanya paling menonjol. --}}
        <section aria-labelledby="judul-kopi" class="flex flex-col rounded-2xl bg-kopi-tua p-5 text-white shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <span class="{{ $lencanaIkon }} bg-white/15">@include('toko.partials.ikon', ['nama' => 'kopi'])</span>
                <h2 id="judul-kopi" class="font-bold">Total panen kopi</h2>
            </div>
            <p class="mt-4 mb-5 text-[clamp(2.25rem,9vw,3rem)] leading-none font-bold break-words">{{ FormatHasil::kg($kopi['panen']) }}</p>
            <div class="mt-auto border-t border-white/25 pt-4">
                <p class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span class="font-bold">Total limbah kopi</span>
                    <span class="text-xl font-bold">{{ FormatHasil::kg($kopi['limbah']) }}</span>
                </p>
                @include('toko.partials.meter', ['persen' => $kopi['persen_limbah'], 'jalur' => 'bg-white/25', 'isi' => 'bg-white'])
                <p class="mt-1 text-sm">{{ FormatHasil::persen($kopi['persen_limbah']) }} dari panen kopi</p>
            </div>
        </section>

        <section aria-labelledby="judul-jamur" class="{{ $kartu }} flex flex-col p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="{{ $lencanaIkon }} bg-kopi/10 text-kopi">@include('toko.partials.ikon', ['nama' => 'jamur'])</span>
                <h2 id="judul-jamur" class="font-bold">Total panen jamur</h2>
            </div>
            <p class="mt-4 mb-5 text-3xl leading-none font-bold break-words">{{ FormatHasil::kg($jamur['total']) }}</p>
            <div class="mt-auto border-t border-garis-halus pt-4">
                <p class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span class="font-bold">Panen jamur dengan cangkang kopi</span>
                    <span class="text-xl font-bold">{{ FormatHasil::kg($jamur['cangkang']) }}</span>
                </p>
                @include('toko.partials.meter', ['persen' => $jamur['persen_cangkang'], 'jalur' => 'bg-kopi/15', 'isi' => 'bg-kopi'])
                <p class="mt-1 text-sm text-redup">{{ FormatHasil::persen($jamur['persen_cangkang']) }} dari total panen jamur</p>
            </div>
        </section>

        <section aria-labelledby="judul-penjualan" class="{{ $kartu }} flex flex-col p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="{{ $lencanaIkon }} bg-kopi/10 text-kopi">@include('toko.partials.ikon', ['nama' => 'uang'])</span>
                <h2 id="judul-penjualan" class="font-bold">Total uang penjualan jamur</h2>
            </div>
            <p class="mt-4 mb-5 text-3xl leading-none font-bold break-words">{{ FormatHasil::rupiah($penjualan['rupiah']) }}</p>
            <div class="mt-auto border-t border-garis-halus pt-4">
                <p class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span class="font-bold">Total jamur terjual</span>
                    <span class="text-xl font-bold">{{ FormatHasil::kg($penjualan['kg']) }}</span>
                </p>
            </div>
        </section>
    </div>

    <section aria-labelledby="judul-baglog" class="{{ $kartu }} mt-4 p-5 sm:p-6">
        <h2 id="judul-baglog" class="text-xl font-bold">Baglog jamur: dengan dan tanpa cangkang kopi</h2>
        <p class="mt-1 text-redup">Perbandingan hasil jamur dari dua jenis media tanam.</p>
        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach ($perbandingan as $m)
                <div class="rounded-xl bg-kosong-lembut p-4">
                    <h3 class="flex items-center gap-2 font-bold">
                        @include('toko.partials.ikon', ['nama' => 'jamur', 'kelas' => 'size-5 text-kopi'])
                        {{ $m['label'] }}
                    </h3>
                    <dl class="mt-3 divide-y divide-garis">
                        @foreach ([
                            'Jumlah baglog' => number_format($m['baglog'], 0, ',', '.').' baglog',
                            'Total panen jamur' => FormatHasil::kg($m['panen']),
                            'Hasil per baglog' => $m['kg_per_baglog'] === null ? 'Belum ada data' : FormatHasil::kg($m['kg_per_baglog']),
                            'Jamur terjual' => FormatHasil::kg($m['terjual_kg']),
                        ] as $label => $nilai)
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 py-2">
                                <dt class="text-redup">{{ $label }}</dt>
                                <dd class="font-bold">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="judul-rincian"class="{{ $kartu }} mt-4 p-5 sm:p-6">
        <h2 id="judul-rincian" class="text-xl font-bold">Rincian panen kopi</h2>

        @if (empty($kopi['per_jenis']))
            <div class="mt-4 flex flex-col items-center rounded-xl bg-kosong-lembut px-4 py-8 text-center">
                @include('toko.partials.ikon', ['nama' => 'kopi', 'kelas' => 'size-8 text-redup'])
                <p class="mt-2 font-bold">Belum ada data panen kopi pada pilihan ini.</p>
                <p class="mt-1 text-sm text-redup">Coba pilih Semua waktu atau hapus pilihan jenis dan tempat.</p>
            </div>
        @else
            <div role="tablist" aria-labelledby="judul-rincian" class="mt-4 flex flex-wrap gap-1 rounded-xl bg-kosong-lembut p-1">
                @foreach ($tabRincian as $i => [$kunci, $label])
                    <button type="button" role="tab" id="tab-{{ $kunci }}" aria-controls="panel-{{ $kunci }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? 0 : -1 }}"
                        class="min-h-11 flex-1 rounded-lg px-2 text-sm whitespace-nowrap text-redup sm:px-3 sm:text-base transition-colors aria-selected:bg-permukaan aria-selected:font-bold aria-selected:text-tinta aria-selected:shadow-sm">{{ $label }}</button>
                @endforeach
            </div>

            @foreach ($tabRincian as $i => [$kunci, $label, $judul, $baris])
                <div id="panel-{{ $kunci }}" role="tabpanel" aria-labelledby="tab-{{ $kunci }}" data-panel-rincian @class(["mt-4", "hidden" => $i > 0])>
                    @include('toko.partials.rincian', ['judul' => $judul, 'baris' => $baris])
                    @if ($kunci === 'hari' && $kopi['hari_terpotong'])
                        <p class="mt-2 text-sm text-redup">Menampilkan {{ RekapHasil::BATAS_HARIAN }} hari panen terbaru. Pilih tanggal untuk melihat hari yang lebih lama.</p>
                    @endif
                </div>
            @endforeach
        @endif
    </section>
@endsection
