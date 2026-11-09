// Dashboard EWS. Semua status, teks, dan level sudah dihitung server; berkas ini hanya menampilkan.
const akar = document.getElementById('dasbor');
const el = (id) => document.getElementById(id);
const IKON = { hijau: 'aman', kuning: 'waspada', merah: 'awas', kosong: 'kosong' };
const JEDA_NORMAL = 10_000;
const JEDA_MAKS = 60_000;

let data = JSON.parse(el('data-awal').textContent);
let wilayahAktif = null;
let wilayahDipilihWarga = false;
let statusSebelum = data.utama.status;
let peta = null;
let grafik = null;
let etagTerakhir = null;
let gagalBeruntun = 0;
let waktuTerakhir = null;
let sensorGrafik = 'kelembapan_tanah';
let rentangGrafik = '24j';

function isi(wadah, nama, nilai) {
    const sasaran = wadah.querySelector(`[data-isi="${nama}"]`);
    if (sasaran) sasaran.textContent = nilai ?? '';
    return sasaran;
}

function cetak(id) {
    return el(id).content.firstElementChild.cloneNode(true);
}

function lencana(warna, kata) {
    const node = cetak('cetak-lencana');
    node.dataset.warna = warna;
    node.querySelector('use').setAttribute('href', `#i-${IKON[warna]}`);
    isi(node, 'kata', kata);
    return node;
}

function tampilkanPanel() {
    const { utama, offline } = data;
    el('panel').dataset.warna = utama.warna;
    el('panel-ikon').setAttribute('href', `#i-${IKON[utama.warna]}`);
    el('panel-status').textContent = utama.status ?? 'Belum ada data';
    el('panel-saran').textContent = utama.saran;
    el('panel-lokasi').textContent = utama.lokasi ?? '';
    el('panel-pemicu').textContent = utama.pemicu ?? '';
    el('panel-waktu').textContent = utama.waktu ? `${utama.waktu} WIB` : '-';

    el('banner-alat').classList.toggle('hidden', offline.length === 0);
    el('banner-alat-teks').textContent = offline
        .map((a) => `Alat tidak mengirim data: ${a.nama} (terakhir ${a.waktu} WIB).`)
        .join(' ');

    if (utama.status !== statusSebelum) {
        el('pengumuman').textContent = `Status berubah menjadi ${utama.status ?? 'belum ada data'}. ${utama.saran}`;
        statusSebelum = utama.status;
    }
}

function tampilkanDaftar() {
    const wadah = el('daftar-wilayah');

    // Tombol dipakai ulang antar pembaruan agar fokus keyboard tidak hilang tiap beberapa detik.
    data.wilayah.forEach((w) => {
        let tombol = wadah.querySelector(`[data-id="${w.id}"]`);
        if (!tombol) {
            tombol = cetak('cetak-wilayah');
            tombol.dataset.id = w.id;
            tombol.addEventListener('click', () => pilihWilayah(w.id, true));
            wadah.append(tombol);
        }
        tombol.setAttribute('aria-pressed', String(w.id === wilayahAktif));
        isi(tombol, 'nama', w.nama);
        isi(tombol, 'info', w.offline ? 'Alat tidak mengirim data' : (w.waktu ? `Data ${w.waktu} WIB` : 'Belum ada data'));
        isi(tombol, 'lencana', '').append(lencana(w.warna, w.status ?? 'Belum ada data'));
    });

    wadah.querySelectorAll('[data-id]').forEach((tombol) => {
        if (!data.wilayah.some((w) => String(w.id) === tombol.dataset.id)) tombol.remove();
    });
}

function tampilkanSensor() {
    const w = data.wilayah.find((x) => x.id === wilayahAktif);
    const wadah = el('kartu-sensor');
    el('sensor-wilayah').textContent = w ? `· ${w.nama}` : '';
    el('sensor-waktu').textContent = w?.waktu ? `Data ${w.waktu} WIB` : '';

    // Batang pengukur hanya beranimasi saat kartu pertama kali tampil, bukan di setiap pembaruan berkala.
    const pertama = !wadah.dataset.sudahTampil;
    wadah.dataset.sudahTampil = '1';

    wadah.replaceChildren(...Object.entries(w?.sensor ?? {}).map(([kunci, s]) => {
        const kartu = cetak('cetak-sensor');
        kartu.dataset.warna = s.warna;
        kartu.querySelector('[data-isi="ikon"]').setAttribute('href', `#i-${s.ikon}`);
        isi(kartu, 'nama', s.nama);
        isi(kartu, 'teks', s.nilai === null ? '-' : s.teks);
        isi(kartu, 'lencana', '').append(lencana(s.warna, s.kata));
        isiMeter(kartu, kunci, s.nilai, pertama);
        return kartu;
    }));
}

// Batang pengukur hanya gambar bantu (nilai sebenarnya ada di teks). Persen: 0–100.
// Kemiringan: dari tengah ke kiri/kanan sesuai arah, penuh pada 45 derajat.
function isiMeter(kartu, kunci, nilai, beranimasi) {
    const meter = kartu.querySelector('[data-isi="meter"]');
    const batang = kartu.querySelector('[data-isi="isi-meter"]');
    if (!meter || !batang) return;

    let kiri = 0;
    let lebar = 0;
    if (nilai !== null) {
        if (kunci === 'kemiringan') {
            meter.dataset.jenis = 'sudut';
            const separuh = Math.min(50, (Math.abs(nilai) / 45) * 50);
            kiri = nilai >= 0 ? 50 : 50 - separuh;
            lebar = separuh;
        } else {
            lebar = Math.max(0, Math.min(100, nilai));
        }
    }

    const pasang = () => {
        batang.style.left = `${kiri}%`;
        batang.style.width = `${lebar}%`;
    };
    if (beranimasi) {
        batang.style.left = kunci === 'kemiringan' ? '50%' : '0%';
        requestAnimationFrame(() => requestAnimationFrame(pasang));
    } else {
        pasang();
    }
}

function tampilkanLog() {
    el('log').replaceChildren(...data.log.map((k) => {
        const baris = cetak('cetak-log');
        isi(baris, 'lencana', '').append(lencana(k.warna, k.status));
        isi(baris, 'wilayah', k.wilayah);
        isi(baris, 'keterangan', k.keterangan);
        isi(baris, 'waktu', `${k.waktu} WIB`);
        return baris;
    }));
    el('log-kosong').classList.toggle('hidden', data.log.length > 0);
}

function pilihWilayah(id, olehWarga = false) {
    wilayahAktif = id;
    wilayahDipilihWarga = wilayahDipilihWarga || olehWarga;
    tampilkanDaftar();
    tampilkanSensor();
    peta?.perbarui(data.wilayah, wilayahAktif);
    if (olehWarga) peta?.fokus(id);
    grafik?.muat(wilayahAktif, sensorGrafik, rentangGrafik);
}

function tampilkanSemua() {
    // Sebelum warga memilih, ikuti wilayah paling berbahaya agar yang terlihat adalah yang terpenting.
    if (!wilayahDipilihWarga || !data.wilayah.some((w) => w.id === wilayahAktif)) {
        wilayahAktif = data.wilayah.find((w) => w.nama === data.utama.lokasi)?.id ?? data.wilayah[0]?.id ?? null;
    }
    tampilkanPanel();
    tampilkanDaftar();
    tampilkanSensor();
    tampilkanLog();
    peta?.perbarui(data.wilayah, wilayahAktif);
}

function tampilkanKoneksi() {
    const jam = waktuTerakhir?.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    el('koneksi').textContent = gagalBeruntun > 0
        ? `Koneksi terputus, mencoba lagi. Menampilkan data terakhir${jam ? ` (diperiksa pukul ${jam})` : ''}.`
        : `Terhubung. Terakhir diperbarui pukul ${jam}.`;
}

async function ambilData() {
    if (document.hidden) return; // Tab tak terlihat tidak perlu menghabiskan kuota.

    try {
        const respons = await fetch(akar.dataset.urlData, {
            headers: { Accept: 'application/json' },
            signal: AbortSignal.timeout(8000),
        });
        if (!respons.ok) throw new Error(`HTTP ${respons.status}`);

        const etag = respons.headers.get('ETag');
        if (etag === null || etag !== etagTerakhir) {
            data = await respons.json();
            etagTerakhir = etag;
            tampilkanSemua();
        }
        gagalBeruntun = 0;
        waktuTerakhir = new Date();
    } catch {
        gagalBeruntun += 1;
    }
    tampilkanKoneksi();
}

function jadwalkan() {
    // Saat sinyal hilang, jeda dibuat makin panjang agar tidak terus-menerus membuang baterai.
    const jeda = Math.min(JEDA_MAKS, JEDA_NORMAL * 2 ** gagalBeruntun);
    setTimeout(async () => {
        await ambilData();
        jadwalkan();
    }, jeda);
}

function saatTerlihat(elemen, jalankan) {
    new IntersectionObserver((isian, pengamat) => {
        if (isian.some((i) => i.isIntersecting)) {
            pengamat.disconnect();
            jalankan();
        }
    }, { rootMargin: '300px' }).observe(elemen);
}

// Peta dan grafik adalah bagian terberat, jadi baru diunduh saat warga menggulir ke sana.
function siapkanPeta() {
    saatTerlihat(el('peta'), async () => {
        try {
            const { buatPeta } = await import('./lib/peta.js');
            peta = buatPeta(el('peta'), (id) => pilihWilayah(id, true));
            peta.perbarui(data.wilayah, wilayahAktif);
            el('mode-peta').addEventListener('change', (e) => peta.gantiMode(e.target.value));
        } catch {
            el('peta-galat').classList.remove('hidden');
        }
    });
}

function siapkanGrafik() {
    const pilih = (wadahId, kunci, simpan) => el(wadahId).addEventListener('click', (e) => {
        const tombol = e.target.closest('button');
        if (!tombol) return;
        el(wadahId).querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b === tombol)));
        simpan(tombol.dataset[kunci]);
        grafik?.muat(wilayahAktif, sensorGrafik, rentangGrafik);
    });
    pilih('pilih-sensor', 'sensor', (v) => { sensorGrafik = v; });
    pilih('pilih-rentang', 'rentang', (v) => { rentangGrafik = v; });

    saatTerlihat(el('grafik'), async () => {
        try {
            const { buatGrafik } = await import('./lib/grafik.js');
            grafik = buatGrafik(el('grafik'), {
                url: akar.dataset.urlGrafik,
                pesan: el('grafik-pesan'),
                ringkas: el('grafik-ringkas'),
                wilayah: el('grafik-wilayah'),
            });
            grafik.muat(wilayahAktif, sensorGrafik, rentangGrafik);
            setInterval(() => {
                if (!document.hidden) grafik.muat(wilayahAktif, sensorGrafik, rentangGrafik);
            }, 60_000);
        } catch {
            el('grafik-pesan').textContent = 'Grafik gagal dimuat. Periksa koneksi lalu muat ulang halaman.';
            el('grafik-pesan').classList.replace('hidden', 'grid');
        }
    });
}

tampilkanSemua();
siapkanPeta();
siapkanGrafik();
waktuTerakhir = new Date();
tampilkanKoneksi();
jadwalkan();

// Begitu sinyal kembali atau tab dibuka lagi, data langsung disegarkan tanpa menunggu jadwal.
window.addEventListener('online', ambilData);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) ambilData();
});
