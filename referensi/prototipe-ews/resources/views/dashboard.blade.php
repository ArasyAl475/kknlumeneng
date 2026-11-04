@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .item-daerah { display: block; width: 100%; text-align: left; cursor: pointer; border: 1px solid #E3E8EB; }
    .item-daerah:hover { background: #F6F9FA; }
    .item-daerah[aria-pressed="true"] { background: #EEF2F4; box-shadow: inset 0 0 0 3px #14232B; }
    .tab-sensor { border: 1px solid #cbd5e1; background: #fff; }
    .tab-sensor:hover { background: #F6F9FA; }
    .tab-sensor[aria-selected="true"], .tab-sensor[aria-selected="true"]:hover { background: #14232B; color: #fff; border-color: #14232B; }
    .kontrol-mode { background: #fff; padding: .5rem .6rem; border-radius: .5rem; box-shadow: 0 1px 5px rgba(0,0,0,.4); font-size: .85rem; line-height: 1.4; }
    .kontrol-mode label { display: block; font-weight: 700; }
    .kontrol-mode select { margin-top: .15rem; border: 1px solid #94a3b8; border-radius: .35rem; padding: .25rem .4rem; }
</style>
@endpush

@section('content')
    {{-- Kartu status utama: warnanya berubah lewat atribut data-warna (diatur JavaScript) --}}
    <section id="panel" class="panel" data-warna="kosong" aria-live="polite">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="font-bold">Status desa saat ini</p>
                <h1 class="mt-1 flex items-center gap-3 text-5xl font-bold md:text-6xl">
                    <i id="panel-ikon" class="fa-solid fa-circle-question"></i>
                    <span id="panel-judul">Belum ada data</span>
                </h1>
                <p id="panel-saran" class="mt-3 max-w-xl text-lg"></p>
            </div>
            <div class="md:text-right">
                <p id="panel-lokasi" class="text-2xl font-bold"></p>
                <p id="panel-pemicu" class="mt-1 max-w-sm text-base"></p>
                <p id="panel-waktu" class="text-sm"></p>
            </div>
        </div>
        <p id="panel-offline" class="mt-4 hidden rounded-lg bg-black/20 px-3 py-2 text-sm font-bold" role="status"></p>
        <p id="koneksi" class="mt-3 text-sm"></p>
    </section>

    {{-- Empat sensor untuk daerah yang dipilih (bawaan: daerah dengan status terburuk) --}}
    <section class="blok">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold">Bacaan sensor: <span id="sensor-lokasi">-</span></h2>
            <p id="sensor-waktu" class="text-sm text-slate-600"></p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" id="kartu-sensor"></div>
    </section>

    {{-- Peta: daftar daerah di kiri, peta di kanan --}}
    <section class="blok">
        <h2 class="mb-3 text-lg font-bold">Peta daerah</h2>
        <div class="grid gap-4 lg:grid-cols-[17rem_1fr]">
            <div>
                <p class="mb-2 text-sm text-slate-600">Klik daerah untuk menuju ke peta.</p>
                <div id="daftar-daerah" class="space-y-2"></div>
                <button id="lihat-semua" type="button" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 font-bold hover:bg-slate-50">
                    <i class="fa-solid fa-expand"></i> Lihat semua daerah
                </button>
            </div>
            <div>
                <div id="peta" class="h-[26rem] rounded-lg lg:h-[34rem]"></div>
                <p class="mt-2 text-sm text-slate-600">Batas daerah masih contoh. Ganti di config/ews.php.</p>
            </div>
        </div>
    </section>

    <section class="blok">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-bold">Grafik sensor</h2>
            <div id="tab-grafik" class="flex flex-wrap gap-2" role="tablist" aria-label="Pilih sensor untuk grafik"></div>
        </div>
        <div class="h-72"><canvas id="grafik"></canvas></div>
        <p class="mt-2 text-sm text-slate-600">Menampilkan 60 bacaan terakhir tiap daerah. Garis putus-putus = batas waspada dan batas validasi.</p>
    </section>

    <section class="blok">
        <h2 class="mb-3 text-lg font-bold">10 data terakhir</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-2 pr-4">Waktu</th><th class="py-2 pr-4">Lokasi</th>
                        <th class="py-2 pr-4">Tanah</th><th class="py-2 pr-4">Hujan</th><th class="py-2 pr-4">Miring</th><th class="py-2 pr-4">Udara</th><th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody id="tabel-log"></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const URL_DATA = @json(route('data.terbaru'));
    let payload = @json($payload);   // data awal dari server, lalu diganti hasil polling
    let aktif = null;                // nama daerah yang sedang dipilih

    const STATUS = {
        hijau:  { judul: 'Aman',    ikon: 'fa-circle-check',         saran: 'Semua sensor normal. Tetap pantau halaman ini.' },
        kuning: { judul: 'Waspada', ikon: 'fa-triangle-exclamation', saran: 'Tanah mulai jenuh air, hujan deras, atau lereng mulai bergerak. Hindari lereng dan siapkan jalur evakuasi.' },
        merah:  { judul: 'Awas',    ikon: 'fa-circle-exclamation',   saran: 'Kabel EWS putus atau admin telah memvalidasi bahaya longsor. Menjauh dari lereng dan ikuti arahan Karang Taruna dan perangkat desa.' },
        kosong: { judul: 'Belum ada data', ikon: 'fa-circle-question', saran: 'Menunggu data pertama dari alat sensor.' }
    };
    const WARNA_PETA  = { hijau: '#1E7F55', kuning: '#F2B705', merah: '#B3261E', kosong: '#6B7780' };
    const WARNA_GARIS = ['#2563EB', '#7C3AED', '#0891B2', '#B45309'];

    // Empat sensor. 'kunci' = nama field di data, 'batas' = nama ambang di payload.batas (kosong = hanya informasi).
    const SENSOR = {
        tanah:  { nama: 'Kelembapan tanah', pendek: 'Tanah',  ikon: 'fa-droplet',        satuan: '%',       kunci: 'kelembapan',       batas: ['waspada', 'validasi'],               maks: 100 },
        hujan:  { nama: 'Curah hujan',      pendek: 'Hujan',  ikon: 'fa-cloud-rain',     satuan: ' mm/jam', kunci: 'curah_hujan',      batas: ['hujan_waspada', 'hujan_validasi'],   maks: null },
        miring: { nama: 'Kemiringan',       pendek: 'Miring', ikon: 'fa-ruler-combined', satuan: '°',       kunci: 'kemiringan',       batas: ['miring_waspada', 'miring_validasi'], maks: null },
        udara:  { nama: 'Kelembapan udara', pendek: 'Udara',  ikon: 'fa-wind',           satuan: '%',       kunci: 'kelembapan_udara', batas: null,                                 maks: 100 }
    };
    const LEVEL_WARNA = { 0: 'hijau', 1: 'kuning', 2: 'kuning' };
    const LEVEL_TEKS  = { 0: 'Normal', 1: 'Waspada', 2: 'Perlu validasi' };

    // 63 -> "63", 12.5 -> "12,5", kosong -> "--"
    const angka = v => (v === null || v === undefined) ? '--' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const nilaiSensor = (d, k) => d ? angka(d[SENSOR[k].kunci]) + (d[SENSOR[k].kunci] === null || d[SENSOR[k].kunci] === undefined ? '' : SENSOR[k].satuan) : '--';
    const lamaTeks = m => m < 1 ? 'baru saja' : m < 60 ? `${m} menit lalu` : `${Math.floor(m / 60)} jam ${m % 60} menit lalu`;

    // Mencegah teks berisi tag HTML merusak halaman
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const lencana = d => `<span class="lencana" data-warna="${d.warna}"><i class="fa-solid ${STATUS[d.warna].ikon}"></i>${esc(d.status)}</span>`;

    function renderPanel() {
        const d = payload.utama;
        const w = d ? d.warna : 'kosong';
        const l = d ? payload.lokasi.find(x => x.lokasi === d.lokasi) : null;
        document.getElementById('panel').dataset.warna = w;
        document.getElementById('panel-ikon').className = 'fa-solid ' + STATUS[w].ikon;
        document.getElementById('panel-judul').textContent = STATUS[w].judul;
        document.getElementById('panel-saran').textContent = STATUS[w].saran;
        document.getElementById('panel-lokasi').textContent = d ? d.lokasi : '';
        document.getElementById('panel-pemicu').textContent = d ? ((l && l.awas) || d.pemicu || 'Semua sensor normal') : '';
        document.getElementById('panel-waktu').textContent = d ? d.waktu : '';

        const mati = payload.lokasi.filter(x => x.offline);
        const el = document.getElementById('panel-offline');
        el.classList.toggle('hidden', mati.length === 0);
        el.innerHTML = '<i class="fa-solid fa-plug-circle-xmark"></i> Alat tidak mengirim data: ' +
            mati.map(x => `${esc(x.lokasi)} (terakhir ${lamaTeks(x.umur_menit)})`).join(', ') + '. Angka yang tampil bisa sudah usang.';
    }

    // ----- Empat kartu sensor untuk daerah terpilih -----
    function lokasiSensor() {
        return payload.lokasi.find(l => l.lokasi === aktif)
            || (payload.utama && payload.lokasi.find(l => l.lokasi === payload.utama.lokasi))
            || payload.lokasi.find(l => l.data) || null;
    }
    function renderSensor() {
        const l = lokasiSensor(), d = l ? l.data : null;
        document.getElementById('sensor-lokasi').textContent = l ? l.lokasi : '-';
        document.getElementById('sensor-waktu').textContent = d ? `Data ${d.waktu} (${lamaTeks(l.umur_menit)})` : 'Belum ada data';
        document.getElementById('kartu-sensor').innerHTML = Object.entries(SENSOR).map(([k, s]) => {
            const lv = d && d.level ? d.level[k] : undefined;     // undefined = sensor ini tidak punya ambang (udara)
            const ada = d && d[s.kunci] !== null && d[s.kunci] !== undefined;
            const warna = !ada ? 'kosong' : (lv === undefined ? 'kosong' : LEVEL_WARNA[lv]);
            const ket = !ada ? 'Tidak ada data' : (lv === undefined ? 'Informasi' : LEVEL_TEKS[lv]);
            return `<div class="kartu" data-warna="${warna}">
                <p class="text-sm font-bold"><i class="fa-solid ${s.ikon}"></i> ${s.nama}</p>
                <p class="mt-1 text-3xl font-bold">${nilaiSensor(d, k)}</p>
                <p class="mt-1 text-sm text-slate-600">${ket}</p></div>`;
        }).join('');
    }

    function renderTabel() {
        const baris = payload.log.map(r => `<tr class="border-b border-slate-100">
            <td class="py-2 pr-4">${esc(r.waktu)}</td><td class="py-2 pr-4">${esc(r.lokasi)}</td>
            <td class="py-2 pr-4 font-bold">${nilaiSensor(r, 'tanah')}</td><td class="py-2 pr-4">${nilaiSensor(r, 'hujan')}</td>
            <td class="py-2 pr-4">${nilaiSensor(r, 'miring')}</td><td class="py-2 pr-4">${nilaiSensor(r, 'udara')}</td><td class="py-2">${lencana(r)}</td></tr>`).join('');
        document.getElementById('tabel-log').innerHTML = baris || '<tr><td colspan="7" class="py-4 text-slate-600">Belum ada data. Menunggu kiriman dari alat atau simulator.</td></tr>';
    }

    // ----- Grafik (Chart.js): satu garis per lokasi + dua garis batas. Sumbu X = waktu sebenarnya. -----
    let sensorGrafik = 'tanah';
    const jamMenit = ms => new Date(ms).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    const grafik = new Chart(document.getElementById('grafik'), {
        type: 'line', data: { datasets: [] },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            scales: {
                x: { type: 'linear', ticks: { callback: jamMenit, maxTicksLimit: 8 } },
                y: { min: 0, title: { display: true, text: '' } }
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { title: items => items.length ? new Date(items[0].parsed.x).toLocaleString('id-ID') : '' } }
            }
        }
    });

    function renderTabGrafik() {
        const wadah = document.getElementById('tab-grafik');
        if (!wadah.children.length) {
            wadah.innerHTML = Object.entries(SENSOR).map(([k, s]) =>
                `<button type="button" role="tab" data-sensor="${k}" class="tab-sensor rounded-lg px-3 py-1 text-sm font-bold"><i class="fa-solid ${s.ikon}"></i> ${s.pendek}</button>`).join('');
            wadah.addEventListener('click', e => {
                const b = e.target.closest('[data-sensor]');
                if (b) { sensorGrafik = b.dataset.sensor; renderGrafik(); }
            });
        }
        wadah.querySelectorAll('[data-sensor]').forEach(b => {
            const on = b.dataset.sensor === sensorGrafik;
            b.setAttribute('aria-selected', on);
        });
    }

    function renderGrafik() {
        renderTabGrafik();
        const s = SENSOR[sensorGrafik];
        const set = payload.grafik.map((g, i) => ({
            label: g.lokasi, borderColor: WARNA_GARIS[i % 4], backgroundColor: WARNA_GARIS[i % 4],
            data: g.titik.filter(p => p[sensorGrafik] !== null).map(p => ({ x: p.t, y: p[sensorGrafik] })),
            spanGaps: true, tension: .25, pointRadius: 2
        }));
        const semuaX = set.flatMap(d => d.data.map(p => p.x));
        if (s.batas && semuaX.length) {
            const x0 = Math.min(...semuaX), x1 = Math.max(...semuaX);
            const garis = (nama, nilai, warna) => ({ label: `${nama} (${angka(nilai)}${s.satuan})`, data: [{ x: x0, y: nilai }, { x: x1, y: nilai }], borderColor: warna, borderDash: [6, 6], pointRadius: 0 });
            set.push(garis('Batas waspada', payload.batas[s.batas[0]], '#B8860B'));
            set.push(garis('Batas validasi admin', payload.batas[s.batas[1]], '#B3261E'));
        }
        grafik.data.datasets = set;
        grafik.options.scales.y.title.text = `${s.nama} (${s.satuan.trim()})`;
        grafik.options.scales.y.max = s.maks ?? undefined;
        grafik.options.scales.y.suggestedMax = s.batas ? payload.batas[s.batas[1]] * 1.2 : undefined;
        grafik.update();
    }

    // ----- Peta (Leaflet) -----
    // Mode peta. 'isi' = seberapa pekat warna area. Mode Kerawanan memakai peta terang agar warna area menonjol.
    const MODE = {
        standar:   { nama: 'Standar', isi: .25, buat: () => L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, detectRetina: true, attribution: '&copy; OpenStreetMap' }) },
        satelit:   { nama: 'Satelit (HD)', isi: .2, buat: () => L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, maxNativeZoom: 18, detectRetina: true, attribution: 'Tiles &copy; Esri' }) },
        topografi: { nama: 'Topografi / ketinggian', isi: .25, buat: () => L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { subdomains: 'abc', maxZoom: 19, maxNativeZoom: 17, attribution: 'Peta: &copy; OpenStreetMap, SRTM | Gaya: &copy; OpenTopoMap' }) },
        kerawanan: { nama: 'Kerawanan', isi: .6, buat: () => L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19, attribution: '&copy; OpenStreetMap, &copy; CARTO' }) }
    };
    let modeAktif = 'standar', dasar = null;
    const poligon = {};   // nama daerah -> objek poligon di peta

    // Atur tampilan awal DULU supaya semua daerah terlihat
    const titikSemua = () => payload.lokasi.flatMap(l => l.poligon);
    const peta = L.map('peta', { scrollWheelZoom: false, maxZoom: 19 });
    if (titikSemua().length) peta.fitBounds(titikSemua(), { padding: [40, 40], maxZoom: 17 }); else peta.setView([-7.17, 109.55], 14);

    // Dropdown mode peta di pojok kanan atas
    const kontrolMode = L.control({ position: 'topright' });
    kontrolMode.onAdd = () => {
        const div = L.DomUtil.create('div', 'kontrol-mode');
        div.innerHTML = `<label for="pilih-mode">Mode peta</label><select id="pilih-mode">${Object.entries(MODE).map(([k, m]) => `<option value="${k}">${m.nama}</option>`).join('')}</select>`;
        L.DomEvent.disableClickPropagation(div);
        div.querySelector('select').addEventListener('change', e => gantiMode(e.target.value));
        return div;
    };
    kontrolMode.addTo(peta);

    // Legenda warna di pojok kiri bawah
    const legenda = L.control({ position: 'bottomleft' });
    legenda.onAdd = () => {
        const div = L.DomUtil.create('div', 'kontrol-mode');
        div.innerHTML = ['hijau', 'kuning', 'merah', 'kosong'].map(w =>
            `<div><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:3px;margin-right:.4rem;background:${WARNA_PETA[w]}"></span>${STATUS[w].judul}</div>`).join('');
        return div;
    };
    legenda.addTo(peta);

    function gantiMode(mode) {
        if (dasar) peta.removeLayer(dasar);
        modeAktif = mode;
        dasar = MODE[mode].buat().addTo(peta);
        dasar.bringToBack();
        gayaSemua();
    }

    // Warna garis dan isi tiap daerah mengikuti status terbarunya
    function gayaSemua() {
        payload.lokasi.forEach(l => {
            const w = l.data ? l.data.warna : 'kosong';
            poligon[l.lokasi]?.setStyle({ color: WARNA_PETA[w], fillColor: WARNA_PETA[w], weight: aktif === l.lokasi ? 6 : 3, fillOpacity: MODE[modeAktif].isi });
        });
    }

    const isiPopup = l => `<strong>${esc(l.lokasi)}</strong><br>` +
        (l.data ? `${esc(l.data.status)}<br>Tanah ${nilaiSensor(l.data, 'tanah')}<br>Hujan ${nilaiSensor(l.data, 'hujan')}<br>Miring ${nilaiSensor(l.data, 'miring')}<br>Udara ${nilaiSensor(l.data, 'udara')}<br><small>${esc(l.data.waktu)}${l.offline ? ' (alat tidak mengirim data)' : ''}</small>${l.awas ? '<br><strong>' + esc(l.awas) + '</strong>' : ''}` : 'Belum ada data');

    function renderPeta() {
        payload.lokasi.forEach(l => {
            if (!poligon[l.lokasi]) {
                poligon[l.lokasi] = L.polygon(l.poligon).addTo(peta);
                poligon[l.lokasi].bindPopup('', { autoPan: false }).bindTooltip(l.lokasi, { sticky: true });
                poligon[l.lokasi].on('click', () => pilih(l.lokasi, false));
            }
            poligon[l.lokasi].setPopupContent(isiPopup(l));
        });
        // Buang poligon wilayah yang sudah dihapus admin
        Object.keys(poligon).filter(n => !payload.lokasi.some(l => l.lokasi === n)).forEach(n => { peta.removeLayer(poligon[n]); delete poligon[n]; });
        gayaSemua();
    }

    // Menuju ke satu daerah (dipanggil saat daftar diklik atau poligon diklik)
    function pilih(nama, terbang = true) {
        aktif = nama;
        const p = poligon[nama];
        if (terbang) {
            peta.flyToBounds(p.getBounds(), { padding: [50, 50], maxZoom: 17, duration: 1 });
            p.openPopup(p.getBounds().getCenter());
        }
        renderDaftar();
        renderSensor();
        gayaSemua();
    }

    // Daftar daerah di samping peta. Dibuat sekali, lalu hanya isinya yang diperbarui
    // (supaya tombol tidak hilang fokus tiap 5 detik).
    function renderDaftar() {
        const wadah = document.getElementById('daftar-daerah');
        if (wadah.children.length !== payload.lokasi.length) {
            wadah.innerHTML = payload.lokasi.map(l => `<button type="button" class="kartu item-daerah" data-lokasi="${esc(l.lokasi)}">
                <span class="block font-bold">${esc(l.lokasi)}</span>
                <span class="mt-1 flex items-center justify-between"><span class="nilai text-xl font-bold"></span><span class="badge"></span></span>
                <span class="info mt-1 block text-sm text-slate-600"></span>
                <span class="umur mt-1 block text-xs text-slate-600"></span></button>`).join('');
        }
        payload.lokasi.forEach(l => {
            const b = wadah.querySelector(`[data-lokasi="${CSS.escape(l.lokasi)}"]`);
            const d = l.data;
            b.dataset.warna = d ? d.warna : 'kosong';
            b.setAttribute('aria-pressed', aktif === l.lokasi);
            b.querySelector('.nilai').textContent = d ? 'Tanah ' + nilaiSensor(d, 'tanah') : '--';
            b.querySelector('.info').textContent = d ? `Hujan ${nilaiSensor(d, 'hujan')} · Miring ${nilaiSensor(d, 'miring')} · Udara ${nilaiSensor(d, 'udara')}` : '';
            const umur = b.querySelector('.umur');
            umur.textContent = d ? (l.offline ? 'Alat tidak mengirim data, terakhir ' : 'Data ') + lamaTeks(l.umur_menit) : '';
            umur.classList.toggle('font-bold', !!l.offline);
            b.querySelector('.badge').innerHTML = d ? lencana(d) : '<span class="lencana" data-warna="kosong">Belum ada data</span>';
        });
    }

    document.getElementById('daftar-daerah').addEventListener('click', e => {
        const b = e.target.closest('.item-daerah');
        if (b) pilih(b.dataset.lokasi);
    });

    document.getElementById('lihat-semua').addEventListener('click', () => {
        aktif = null;
        peta.closePopup();
        if (titikSemua().length) peta.flyToBounds(titikSemua(), { padding: [40, 40], maxZoom: 17, duration: 1 });
        renderDaftar();
        renderSensor();
        gayaSemua();
    });

    function renderSemua() { renderPanel(); renderSensor(); renderDaftar(); renderTabel(); renderGrafik(); renderPeta(); }

    function tampilKoneksi(ok) {
        document.getElementById('koneksi').textContent = ok
            ? `Diperbarui otomatis tiap 5 detik. Terakhir: ${payload.diperbarui}`
            : 'Koneksi terputus. Menampilkan data terakhir dan mencoba lagi...';
    }

    // ----- AJAX polling: ambil data baru setiap 5 detik tanpa refresh halaman -----
    async function ambilData() {
        try {
            const res = await fetch(URL_DATA, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (!res.ok) throw new Error(res.status);
            payload = await res.json();
            renderSemua();
            tampilKoneksi(true);
        } catch (e) {
            tampilKoneksi(false);
        }
    }

    gantiMode('standar');
    renderSemua();
    tampilKoneksi(true);
    setInterval(ambilData, 5000);
    window.addEventListener('load', () => peta.invalidateSize());
</script>
@endpush
