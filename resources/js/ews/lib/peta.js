import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const LAPISAN = {
    standar: () => L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }),
    satelit: () => L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        maxNativeZoom: 18,
        attribution: 'Citra &copy; Esri',
    }),
    topografi: () => L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        subdomains: 'abc',
        maxZoom: 19,
        maxNativeZoom: 17,
        attribution: '&copy; OpenStreetMap, SRTM, OpenTopoMap',
    }),
};

// Bentuk garis ikut berbeda agar status di peta tidak hanya dibedakan lewat warna.
const GAYA = {
    hijau: { color: '#0f6b45', fillColor: '#0f6b45', weight: 2, dashArray: null },
    kuning: { color: '#6b4e00', fillColor: '#f2b705', weight: 3, dashArray: '9 6' },
    merah: { color: '#a61b14', fillColor: '#a61b14', weight: 5, dashArray: null },
    kosong: { color: '#56605a', fillColor: '#56605a', weight: 2, dashArray: '2 6' },
};

const gerakDikurangi = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Penanda dibedakan lewat BENTUK (lingkaran, kotak, segitiga), bukan hanya warna. Isinya tetap: tanpa data pengguna.
const PENANDA = {
    pintar: '<svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><circle cx="15" cy="15" r="12" fill="#1e40af" stroke="#fff" stroke-width="3"/><circle cx="15" cy="15" r="4" fill="#fff"/></svg>',
    alarm: '<svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><rect x="4" y="4" width="22" height="22" rx="3" fill="#0f766e" stroke="#fff" stroke-width="3"/><path d="M15 9v7M15 20v1.5" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>',
    rawan: '<svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><path d="M15 3 28 26H2Z" fill="#c2410c" stroke="#fff" stroke-width="3" stroke-linejoin="round"/><path d="M15 12v7M15 22v1" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>',
};
const ikonPenanda = (jenis) => L.divIcon({ className: '', html: PENANDA[jenis], iconSize: [30, 30], iconAnchor: [15, 15] });

// Batas area EWS alarm: garis titik-titik biru kehijauan, beda dari garis status wilayah.
const GAYA_ALARM = { color: '#0f766e', fillColor: '#0f766e', weight: 2, dashArray: '2 6', fillOpacity: 0.12 };

export function buatPeta(wadah, saatDipilih) {
    const peta = L.map(wadah, {
        scrollWheelZoom: false,
        // Supaya halaman tetap bisa digulir dengan satu jari; peta digeser dengan dua jari.
        dragging: !L.Browser.mobile,
        maxZoom: 19,
    }).setView([-7.17, 109.55], 14);

    let dasar = LAPISAN.standar().addTo(peta);
    const poligon = new Map();
    const penandaAlat = new Map();
    const lapisTitik = L.featureGroup().addTo(peta);
    let sudahDipaskan = false;
    let sidikTitik = null;

    // Elemen dengan textContent, bukan teks HTML: nama berasal dari input admin.
    const tooltip = (teks) => {
        const label = document.createElement('span');
        label.textContent = teks;
        return label;
    };

    function paskanSekali() {
        const semua = L.featureGroup([...poligon.values(), ...penandaAlat.values(), ...lapisTitik.getLayers()]);
        if (!sudahDipaskan && semua.getLayers().length) {
            peta.fitBounds(semua.getBounds(), { padding: [24, 24], maxZoom: 17 });
            sudahDipaskan = true;
        }
    }

    return {
        // Titik tanpa data sensor: EWS alarm (dengan batas area bila ada) dan titik rawan longsor.
        perbaruiTitik(daftar = []) {
            // Pembaruan berkala tiap beberapa detik: gambar ulang hanya bila isinya berubah,
            // supaya tooltip yang sedang dibuka tidak berkedip.
            const sidik = JSON.stringify(daftar);
            if (sidik === sidikTitik) return;
            sidikTitik = sidik;
            lapisTitik.clearLayers();
            daftar.forEach((t) => {
                if (t.jenis === 'alarm' && t.poligon) {
                    L.polygon(t.poligon, GAYA_ALARM).bindTooltip(tooltip(`${t.nama}: ${t.jenis_teks}`)).addTo(lapisTitik);
                }
                L.marker([t.lintang, t.bujur], { icon: ikonPenanda(t.jenis), keyboard: false })
                    .bindTooltip(tooltip(`${t.nama}: ${t.jenis_teks}`))
                    .addTo(lapisTitik);
            });
            paskanSekali();
        },

        gantiMode(mode) {
            peta.removeLayer(dasar);
            dasar = LAPISAN[mode]().addTo(peta);
        },

        perbarui(daftar, aktif) {
            daftar.forEach((w) => {
                let bentuk = poligon.get(w.id);
                if (!bentuk) {
                    bentuk = L.polygon(w.poligon).addTo(peta);
                    bentuk.on('click', () => saatDipilih(w.id));
                    bentuk.bindTooltip(document.createElement('span'), { permanent: true, direction: 'center', opacity: 0.95 });
                    poligon.set(w.id, bentuk);
                }
                bentuk.setLatLngs(w.poligon);

                const gaya = GAYA[w.warna];
                bentuk.setStyle({ ...gaya, fillOpacity: w.id === aktif ? 0.45 : 0.25, weight: gaya.weight + (w.id === aktif ? 1 : 0) });

                // Elemen dengan textContent, bukan teks HTML: nama wilayah berasal dari input admin.
                const label = document.createElement('span');
                label.textContent = `${w.nama}: ${w.status ?? 'Belum ada data'}`;
                bentuk.setTooltipContent(label);
            });

            // Penanda lokasi alat EWS pintar; hanya ada bila admin mengisi lintang dan bujur.
            daftar.forEach((w) => {
                let penanda = penandaAlat.get(w.id);
                if (w.lintang === null || w.lintang === undefined) {
                    if (penanda) {
                        peta.removeLayer(penanda);
                        penandaAlat.delete(w.id);
                    }
                    return;
                }
                if (!penanda) {
                    penanda = L.marker([w.lintang, w.bujur], { icon: ikonPenanda('pintar'), keyboard: false }).addTo(peta);
                    penanda.on('click', () => saatDipilih(w.id));
                    penanda.bindTooltip(tooltip(''));
                    penandaAlat.set(w.id, penanda);
                }
                penanda.setLatLng([w.lintang, w.bujur]);
                penanda.setTooltipContent(tooltip(`${w.nama}: alat EWS pintar`));
            });

            poligon.forEach((bentuk, id) => {
                if (!daftar.some((w) => w.id === id)) {
                    peta.removeLayer(bentuk);
                    poligon.delete(id);
                }
            });
            penandaAlat.forEach((penanda, id) => {
                if (!daftar.some((w) => w.id === id)) {
                    peta.removeLayer(penanda);
                    penandaAlat.delete(id);
                }
            });

            paskanSekali();
        },

        fokus(id) {
            const bentuk = poligon.get(id);
            if (!bentuk) return;
            peta.flyToBounds(bentuk.getBounds(), { padding: [40, 40], maxZoom: 17, animate: !gerakDikurangi(), duration: 0.8 });
        },
    };
}
