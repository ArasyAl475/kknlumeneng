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

const gaya = { color: '#1e40af', weight: 3, fillOpacity: 0.25 };
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

// Dua bentuk isian: JSON [[lintang,bujur],...] atau satu "lintang, bujur" per baris. Hanya untuk pratinjau
// di peta; yang menentukan sah atau tidaknya tetap server.
function bacaTitik(teks) {
    const bersih = teks.trim();
    if (bersih.startsWith('[')) return JSON.parse(bersih);
    return bersih.split(/\r?\n/).filter((b) => b.trim() !== '').map((b) => b.split(/[,;]/).map((n) => Number(n.replace(/\s+/g, ''))));
}

const sahKoordinat = (t) => Array.isArray(t) && t.length === 2 && t.every(Number.isFinite) && Math.abs(t[0]) <= 90 && Math.abs(t[1]) <= 180;

// Mengisi koordinat manual ikut memperbarui peta, sebagai jalan lain bagi yang tidak bisa menggambar.
function muatDariIsian() {
    try {
        const titik = bacaTitik(isian.value);
        if (Array.isArray(titik) && titik.length >= 3 && titik.every(sahKoordinat)) {
            pasangBentuk(titik);
            return true;
        }
    } catch {
        // Isian sedang diketik atau belum lengkap: dibiarkan, server yang akan menilai saat disimpan.
    }
    return false;
}
isian.addEventListener('change', muatDariIsian);

// ── Titik lokasi (lintang, bujur): isian, penanda di peta, dan ketuk peta saling menyinkronkan. ──
const isianLintang = document.getElementById('lintang');
const isianBujur = document.getElementById('bujur');
const tombolPilih = document.getElementById('pilih-titik');
const statusTitik = document.getElementById('status-titik');
// Ikon bawaan Leaflet butuh berkas gambar yang tidak ikut ter-bundle, jadi penanda digambar sendiri.
const ikonTitik = L.divIcon({
    className: '',
    html: '<svg width="28" height="28" viewBox="0 0 28 28" aria-hidden="true"><circle cx="14" cy="14" r="11" fill="#1e40af" stroke="#fff" stroke-width="3"/><circle cx="14" cy="14" r="3.5" fill="#fff"/></svg>',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
});
let penanda = null;
let memilihTitik = false;

function bacaIsianTitik() {
    if (isianLintang.value.trim() === '' || isianBujur.value.trim() === '') return null;
    const t = [Number(isianLintang.value.replace(/\s+/g, '')), Number(isianBujur.value.replace(/\s+/g, ''))];
    return sahKoordinat(t) ? t : null;
}

function tulisIsianTitik(latlng) {
    isianLintang.value = latlng.lat.toFixed(6);
    isianBujur.value = latlng.lng.toFixed(6);
}

function taruhPenanda(titik) {
    if (penanda) {
        penanda.setLatLng(titik);
        return;
    }
    penanda = L.marker(titik, { icon: ikonTitik, draggable: true, keyboard: false }).addTo(peta);
    penanda.on('dragend', () => tulisIsianTitik(penanda.getLatLng()));
}

function sinkronDariIsianTitik() {
    const titik = bacaIsianTitik();
    if (titik) {
        taruhPenanda(titik);
        peta.panTo(titik);
    }
}

// Menempel "-7.185390, 109.588069" dari Google Maps di kolom Lintang langsung terpecah ke dua kolom.
isianLintang.addEventListener('paste', (e) => {
    const teks = (e.clipboardData || window.clipboardData).getData('text');
    const bagian = teks.split(/[,;]/);
    if (bagian.length !== 2) return;
    e.preventDefault();
    isianLintang.value = bagian[0].replace(/\s+/g, '');
    isianBujur.value = bagian[1].replace(/\s+/g, '');
    sinkronDariIsianTitik();
});
isianLintang.addEventListener('change', sinkronDariIsianTitik);
isianBujur.addEventListener('change', sinkronDariIsianTitik);

tombolPilih.addEventListener('click', () => {
    memilihTitik = true;
    statusTitik.textContent = 'Ketuk satu tempat di peta untuk menaruh titik.';
});
peta.on('click', (e) => {
    if (!memilihTitik) return;
    memilihTitik = false;
    taruhPenanda(e.latlng);
    tulisIsianTitik(e.latlng);
    statusTitik.textContent = 'Titik dipasang. Penanda bisa digeser untuk menyesuaikan.';
});

const titikAwal = bacaIsianTitik();
if (titikAwal) taruhPenanda(titikAwal);

if (muatDariIsian()) {
    peta.fitBounds(bentuk.getBounds(), { padding: [30, 30], maxZoom: 17 });
} else if (titikAwal) {
    peta.setView(titikAwal, 17);
} else if (lain.getLayers().length) {
    peta.fitBounds(lain.getBounds(), { padding: [30, 30], maxZoom: 16 });
}
