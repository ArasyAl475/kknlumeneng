import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// leaflet-draw mengharapkan L sebagai variabel global.
window.L = L;
await import('leaflet-draw');
await import('leaflet-draw/dist/leaflet.draw.css');

const wadah = document.getElementById('peta-wilayah');
const isian = document.getElementById('poligon');
const statusTeks = document.getElementById('status-gambar');
const tombolMulai = document.getElementById('mulai-gambar');
const tombolUrung = document.getElementById('urungkan');
const tombolSelesai = document.getElementById('selesai-gambar');

const peta = L.map(wadah, { scrollWheelZoom: false }).setView([-7.17, 109.55], 15);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(peta);

const lain = L.featureGroup().addTo(peta);
JSON.parse(wadah.dataset.lainnya).forEach((w) => {
    const label = document.createElement('span');
    label.textContent = w.nama;
    L.polygon(w.poligon, { color: '#56605a', weight: 1, fillOpacity: 0.1, interactive: false }).bindTooltip(label).addTo(lain);
});

const gaya = { color: '#164e32', weight: 3, fillOpacity: 0.25 };
let bentuk = null;

const tulisIsian = () => {
    isian.value = JSON.stringify(bentuk.getLatLngs()[0].map((t) => [Number(t.lat.toFixed(6)), Number(t.lng.toFixed(6))]));
};

function pasangBentuk(titik) {
    if (bentuk) peta.removeLayer(bentuk);
    bentuk = L.polygon(titik, gaya).addTo(peta);
    bentuk.editing.enable();
    bentuk.on('edit', tulisIsian);
    tombolMulai.textContent = 'Gambar ulang';
}

const penggambar = new L.Draw.Polygon(peta, {
    allowIntersection: false,
    showArea: false,
    shapeOptions: gaya,
    drawError: { color: '#a61b14', message: 'Sisi tidak boleh menyilang.' },
    icon: new L.DivIcon({ iconSize: new L.Point(16, 16), className: 'leaflet-div-icon leaflet-editing-icon' }),
    touchIcon: new L.DivIcon({ iconSize: new L.Point(24, 24), className: 'leaflet-div-icon leaflet-editing-icon' }),
});

function modeGambar(aktif) {
    // Atribut hidden, bukan kelas: kelas hidden kalah oleh inline-flex milik komponen tombol.
    tombolUrung.hidden = !aktif;
    tombolSelesai.hidden = !aktif;
    tombolMulai.hidden = aktif;
    statusTeks.textContent = aktif ? 'Ketuk peta untuk menambah titik. Tekan Selesai setelah minimal 3 titik.' : '';
}

tombolMulai.addEventListener('click', () => {
    if (bentuk) {
        peta.removeLayer(bentuk);
        bentuk = null;
        isian.value = '';
    }
    penggambar.enable();
    modeGambar(true);
});
tombolUrung.addEventListener('click', () => penggambar.deleteLastVertex());
tombolSelesai.addEventListener('click', () => {
    if (penggambar._markers && penggambar._markers.length < 3) {
        statusTeks.textContent = 'Butuh minimal 3 titik sebelum selesai.';
        return;
    }
    penggambar.completeShape();
});

peta.on(L.Draw.Event.CREATED, (e) => {
    pasangBentuk(e.layer.getLatLngs()[0]);
    tulisIsian();
    modeGambar(false);
    statusTeks.textContent = 'Bentuk tersimpan di formulir. Titik masih bisa digeser. Jangan lupa tekan Simpan wilayah.';
});
peta.on('draw:editvertex', () => {
    if (bentuk) tulisIsian();
});

// Mengisi koordinat manual ikut memperbarui peta, sebagai jalan lain bagi yang tidak bisa menggambar.
function muatDariIsian() {
    try {
        const titik = JSON.parse(isian.value);
        if (Array.isArray(titik) && titik.length >= 3) {
            pasangBentuk(titik);
            return true;
        }
    } catch {
        // Isian sedang diketik atau belum lengkap: dibiarkan, server yang akan menilai saat disimpan.
    }
    return false;
}
isian.addEventListener('change', muatDariIsian);

if (muatDariIsian()) {
    peta.fitBounds(bentuk.getBounds(), { padding: [30, 30], maxZoom: 17 });
} else if (lain.getLayers().length) {
    peta.fitBounds(lain.getBounds(), { padding: [30, 30], maxZoom: 16 });
}
