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

export function buatPeta(wadah, saatDipilih) {
    const peta = L.map(wadah, {
        scrollWheelZoom: false,
        // Supaya halaman tetap bisa digulir dengan satu jari; peta digeser dengan dua jari.
        dragging: !L.Browser.mobile,
        maxZoom: 19,
    }).setView([-7.17, 109.55], 14);

    let dasar = LAPISAN.standar().addTo(peta);
    const poligon = new Map();
    let sudahDipaskan = false;

    return {
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

            poligon.forEach((bentuk, id) => {
                if (!daftar.some((w) => w.id === id)) {
                    peta.removeLayer(bentuk);
                    poligon.delete(id);
                }
            });

            if (!sudahDipaskan && poligon.size) {
                peta.fitBounds(L.featureGroup([...poligon.values()]).getBounds(), { padding: [24, 24], maxZoom: 17 });
                sudahDipaskan = true;
            }
        },

        fokus(id) {
            const bentuk = poligon.get(id);
            if (!bentuk) return;
            peta.flyToBounds(bentuk.getBounds(), { padding: [40, 40], maxZoom: 17, animate: !gerakDikurangi(), duration: 0.8 });
        },
    };
}
