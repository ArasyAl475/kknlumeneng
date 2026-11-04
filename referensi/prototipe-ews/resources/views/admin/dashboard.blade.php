@extends('layouts.app')
@section('title', 'Admin')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
@endpush

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-bold">Dashboard admin</h1>
        <a href="{{ route('simulator') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 font-bold hover:bg-slate-50">
            <i class="fa-solid fa-flask"></i> Simulator data
        </a>
    </div>

    @if (session('sukses'))
        <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-emerald-900">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
            @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- 1. Laporan yang perlu divalidasi --}}
    <section class="blok">
        <h2 class="text-lg font-bold">Laporan menunggu validasi
            @if ($menunggu->count())<span class="lencana" data-warna="kuning">{{ $menunggu->count() }}</span>@endif
        </h2>
        <p class="mb-3 text-sm text-slate-600">Ada sensor yang melewati batas validasi (tanah {{ $ambang['validasi'] }}%, hujan {{ $ambang['hujan_validasi'] }} mm/jam, kemiringan {{ $ambang['miring_validasi'] }}°). Periksa kondisi lapangan, lalu putuskan. Laporan baru untuk wilayah yang sama tidak dibuat lagi selama masih menunggu atau baru diputuskan.</p>
        @if ($menunggu->isEmpty())
            <p class="text-slate-600">Tidak ada laporan yang menunggu.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b border-slate-200"><th class="py-2 pr-4">Waktu</th><th class="py-2 pr-4">Lokasi</th><th class="py-2 pr-4">Tanah</th><th class="py-2 pr-4">Hujan</th><th class="py-2 pr-4">Miring</th><th class="py-2 pr-4">Udara</th><th class="py-2 pr-4">Pemicu</th><th class="py-2">Keputusan</th></tr></thead>
                    <tbody>
                    @foreach ($menunggu as $m)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4">{{ $m->created_at->format('d-m-Y H:i:s') }}</td>
                            <td class="py-2 pr-4">{{ $m->lokasi }}</td>
                            <td class="py-2 pr-4 font-bold">{{ $m->kelembapan !== null ? $m->kelembapan.'%' : '-' }}</td>
                            <td class="py-2 pr-4">{{ $m->curah_hujan !== null ? rtrim(rtrim(number_format($m->curah_hujan, 2, ',', ''), '0'), ',').' mm/jam' : '-' }}</td>
                            <td class="py-2 pr-4">{{ $m->kemiringan !== null ? rtrim(rtrim(number_format($m->kemiringan, 2, ',', ''), '0'), ',').'°' : '-' }}</td>
                            <td class="py-2 pr-4">{{ $m->kelembapan_udara !== null ? rtrim(rtrim(number_format($m->kelembapan_udara, 2, ',', ''), '0'), ',').'%' : '-' }}</td>
                            <td class="py-2 pr-4">{{ $m->pemicu ?? '-' }}</td>
                            <td class="flex flex-wrap gap-2 py-2">
                                <form method="POST" action="{{ route('admin.validasi', $m) }}" onsubmit="return confirm('Nyatakan {{ $m->lokasi }} berstatus AWAS dan kirim Telegram?')">
                                    @csrf <input type="hidden" name="keputusan" value="setujui">
                                    <button class="rounded-lg bg-[#B3261E] px-3 py-1 font-bold text-white hover:opacity-90">Nyatakan AWAS</button>
                                </form>
                                <form method="POST" action="{{ route('admin.validasi', $m) }}">
                                    @csrf <input type="hidden" name="keputusan" value="tolak">
                                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-bold hover:bg-slate-50">Tolak (bukan longsor)</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- 2. Wilayah berstatus Awas --}}
    @php $awas = $wilayah->where('status_awas', true); @endphp
    @if ($awas->count())
        <section class="blok border-2 border-[#B3261E]">
            <h2 class="mb-3 text-lg font-bold text-[#B3261E]"><i class="fa-solid fa-circle-exclamation"></i> Wilayah berstatus AWAS</h2>
            @foreach ($awas as $w)
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-2">
                    <div><strong>{{ $w->nama }}</strong><br><span class="text-sm text-slate-600">{{ $w->awas_sumber }} sejak {{ $w->awas_sejak?->format('d-m-Y H:i') }}</span></div>
                    <form method="POST" action="{{ route('admin.cabut', $w) }}" onsubmit="return confirm('Cabut status Awas {{ $w->nama }}? Pastikan kondisi sudah aman.')">
                        @csrf <button class="rounded-lg border border-slate-300 px-3 py-1 font-bold hover:bg-slate-50">Cabut status Awas</button>
                    </form>
                </div>
            @endforeach
        </section>
    @endif

    {{-- 3. Ambang batas --}}
    <section class="blok">
        <h2 class="text-lg font-bold">Ambang batas sensor</h2>
        <ul class="mb-3 mt-1 list-disc pl-5 text-sm text-slate-700">
            <li>Semua sensor di bawah batas waspada = <strong>Aman</strong> (hijau).</li>
            <li>Salah satu sensor mencapai batas waspada = <strong>Waspada</strong> (kuning).</li>
            <li>Salah satu sensor mencapai batas validasi = tetap Waspada, tetapi <strong>menunggu keputusan admin</strong>.</li>
            <li><strong>Awas</strong> (merah) hanya muncul otomatis saat kabel EWS putus, atau setelah admin menyetujui.</li>
            <li>Kelembapan udara hanya ditampilkan sebagai informasi, tidak ikut menentukan status.</li>
        </ul>
        <form method="POST" action="{{ route('admin.ambang') }}" class="space-y-4">
            @csrf
            @php
                $kelompok = [
                    ['Kelembapan tanah (%)', 'batas_waspada', 'waspada', 'batas_validasi', 'validasi', '1', '100'],
                    ['Curah hujan (mm per jam)', 'batas_hujan_waspada', 'hujan_waspada', 'batas_hujan_validasi', 'hujan_validasi', '0.1', '1000'],
                    ['Kemiringan (derajat)', 'batas_miring_waspada', 'miring_waspada', 'batas_miring_validasi', 'miring_validasi', '0.1', '90'],
                ];
            @endphp
            @foreach ($kelompok as [$judul, $kw, $aw, $kv, $av, $min, $maks])
                <div class="flex flex-wrap items-end gap-4">
                    <p class="w-full font-bold sm:w-56">{{ $judul }}</p>
                    <div><label for="{{ $kw }}" class="block text-sm">Batas waspada</label>
                        <input id="{{ $kw }}" name="{{ $kw }}" type="number" min="{{ $min }}" max="{{ $maks }}" step="any" required value="{{ old($kw, $ambang[$aw]) }}" class="w-36 rounded-lg border border-slate-300 p-2"></div>
                    <div><label for="{{ $kv }}" class="block text-sm">Batas validasi</label>
                        <input id="{{ $kv }}" name="{{ $kv }}" type="number" min="{{ $min }}" max="{{ $maks }}" step="any" required value="{{ old($kv, $ambang[$av]) }}" class="w-36 rounded-lg border border-slate-300 p-2"></div>
                </div>
            @endforeach
            <button class="rounded-lg bg-[#14232B] px-4 py-2 font-bold text-white hover:bg-slate-700">Simpan</button>
        </form>
    </section>

    {{-- 4. Tambah wilayah --}}
    <section class="blok">
        <h2 class="text-lg font-bold">Tambah wilayah</h2>
        <p class="mb-3 text-sm text-slate-600">Gambar bentuknya di peta: <strong>segi empat</strong> (ikon kotak) atau <strong>segi banyak</strong> (ikon pentagon). Untuk segitiga, pakai segi banyak, klik 3 titik, lalu klik titik pertama untuk menutup. Garis tidak diterima.</p>
        <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
            <div id="peta-admin" class="h-96 rounded-lg lg:h-[30rem]"></div>
            <form method="POST" action="{{ route('admin.wilayah.tambah') }}" class="space-y-3">
                @csrf
                <div><label for="nama" class="mb-1 block font-bold">Nama daerah</label>
                    <input id="nama" name="nama" required maxlength="100" value="{{ old('nama') }}" class="w-full rounded-lg border border-slate-300 p-3"></div>
                <input type="hidden" name="poligon" id="poligon" value="{{ old('poligon') }}">
                <p id="info-bentuk" class="text-sm text-slate-700">Belum ada bentuk yang digambar.</p>
                <button id="simpan-wilayah" disabled class="w-full rounded-lg bg-[#14232B] p-3 font-bold text-white hover:bg-slate-700 disabled:opacity-40">Simpan daerah</button>
            </form>
        </div>
    </section>

    {{-- 5. Daftar wilayah --}}
    <section class="blok">
        <h2 class="mb-3 text-lg font-bold">Daftar wilayah</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-slate-200"><th class="py-2 pr-4">ID alat</th><th class="py-2 pr-4">Nama</th><th class="py-2 pr-4">Titik batas</th><th class="py-2 pr-4">Data terakhir</th><th class="py-2 pr-4">Status</th><th class="py-2">Aksi</th></tr></thead>
                <tbody>
                @forelse ($wilayah as $w)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 pr-4 font-bold">{{ $w->id }}</td>
                        <td class="py-2 pr-4 font-bold">{{ $w->nama }}</td>
                        <td class="py-2 pr-4">{{ count($w->poligon) }}</td>
                        @php $t = $terakhir[$w->nama] ?? null; $menit = $t ? max(0, (int) $t->created_at->diffInMinutes(now(), true)) : null; @endphp
                        <td class="py-2 pr-4">
                            @if ($t)
                                {{ $t->created_at->format('d-m-Y H:i') }}
                                @if ($offlineMenit > 0 && $menit >= $offlineMenit)<span class="lencana" data-warna="kuning">Tidak mengirim {{ $menit }} menit</span>@endif
                            @else
                                <span class="text-slate-600">Belum pernah</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4">{!! $w->status_awas ? '<span class="lencana" data-warna="merah">Awas</span>' : '-' !!}</td>
                        <td class="py-2">
                            <form method="POST" action="{{ route('admin.wilayah.hapus', $w) }}" onsubmit="return confirm('Hapus daerah {{ $w->nama }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-slate-300 px-3 py-1 hover:bg-slate-50">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-slate-600">Belum ada wilayah. Gambar satu di peta di atas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-slate-600">Alat mengirim data memakai <strong>wilayah_id</strong> (kolom ID alat) atau <strong>lokasi</strong> (nama persis). Panduan lengkap: <code>docs/iot/PANDUAN_API_ESP32.md</code>.</p>
    </section>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    const WILAYAH = @json($wilayahPeta);

    const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, detectRetina: true, attribution: '&copy; OpenStreetMap' });
    const satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, maxNativeZoom: 18, detectRetina: true, attribution: 'Tiles &copy; Esri' });

    const peta = L.map('peta-admin', { layers: [osm], maxZoom: 19, scrollWheelZoom: false });
    L.control.layers({ 'Standar': osm, 'Satelit (HD)': satelit }).addTo(peta);

    // Wilayah yang sudah ada (biru, atau merah jika Awas)
    const titik = WILAYAH.flatMap(w => w.poligon);
    WILAYAH.forEach(w => L.polygon(w.poligon, { color: w.awas ? '#B3261E' : '#2563EB', weight: 2, fillOpacity: .15 }).bindTooltip(w.nama, { sticky: true }).addTo(peta));
    if (titik.length) peta.fitBounds(titik, { padding: [30, 30], maxZoom: 16 }); else peta.setView([-7.17, 109.55], 14);

    // Alat gambar: HANYA segi empat dan segi banyak (tidak ada garis, lingkaran, atau penanda)
    const gambar = new L.FeatureGroup().addTo(peta);
    const bentuk = { color: '#B45309', weight: 3 };
    peta.addControl(new L.Control.Draw({
        draw: {
            polygon: { allowIntersection: false, showArea: false, shapeOptions: bentuk },
            rectangle: { showArea: false, shapeOptions: bentuk },
            polyline: false, circle: false, marker: false, circlemarker: false
        },
        edit: { featureGroup: gambar }
    }));

    const isian = document.getElementById('poligon');
    const info = document.getElementById('info-bentuk');
    const tombol = document.getElementById('simpan-wilayah');

    function isiForm() {
        const lapisan = gambar.getLayers()[0];
        if (!lapisan) { isian.value = ''; info.textContent = 'Belum ada bentuk yang digambar.'; tombol.disabled = true; return; }
        const pts = lapisan.getLatLngs()[0].map(p => [+p.lat.toFixed(6), +p.lng.toFixed(6)]);
        isian.value = JSON.stringify(pts);
        info.textContent = `Bentuk siap disimpan (${pts.length} titik).`;
        tombol.disabled = pts.length < 3;
    }
    // Hanya satu bentuk dalam satu waktu
    peta.on(L.Draw.Event.CREATED, e => { gambar.clearLayers(); gambar.addLayer(e.layer); isiForm(); });
    peta.on(L.Draw.Event.EDITED, isiForm);
    peta.on(L.Draw.Event.DELETED, isiForm);
    window.addEventListener('load', () => peta.invalidateSize());
</script>
@endpush
