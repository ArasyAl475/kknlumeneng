// Kartu status utama (components/kartu-status.blade.php dengan langsung=true), dipakai dasbor EWS
// dan beranda. Semua status, warna, dan teks sudah dihitung server; berkas ini hanya menampilkan.
const el = (id) => document.getElementById(id);

export const IKON = { hijau: 'aman', kuning: 'waspada', merah: 'awas', kosong: 'kosong' };

// Teks penanda dan tingkat di kartu status; harus sama dengan $teks di components/kartu-status.blade.php.
const TEKS_KARTU = {
    hijau: { penanda: 'Kondisi normal', tingkat: 'Tingkat 1' },
    kuning: { penanda: 'Perhatian warga', tingkat: 'Tingkat 2' },
    merah: { penanda: 'Darurat aktif', tingkat: 'Tingkat 3' },
    kosong: { penanda: 'Menunggu data', tingkat: null },
};

// Undefined = belum pernah ditampilkan, jadi tampilan pertama (dari server) tidak diumumkan sebagai perubahan.
let statusSebelum;

// Isi satu bagian kartu status; nilai kosong berarti bagiannya disembunyikan.
// `wadah`: elemen yang ikut disembunyikan (mis. kotak sel), bawaannya elemen itu sendiri.
function isiBagian(id, nilai, wadah = (e) => e) {
    const teks = el(id);
    teks.textContent = nilai ?? '';
    wadah(teks).hidden = !nilai;
}

// Kalimatnya disusun server (alat yang diam saat Waspada mendapat kalimat yang lebih keras).
function teksOfflineBawaan(offline) {
    return offline.map((a) => a.teks).join(' ');
}

/**
 * @param data  isi /api/ews/data
 * @param pilihan.saran        (utama) => teks saran; bawaannya saran dari server
 * @param pilihan.teksOffline  (offline) => teks peringatan alat mati; bawaannya daftar per wilayah
 */
export function tampilkanPanel(data, { saran = (u) => u.saran, teksOffline = teksOfflineBawaan } = {}) {
    const { utama, offline, wilayah } = data;
    const teks = TEKS_KARTU[utama.warna] ?? TEKS_KARTU.kosong;
    el('panel').dataset.warna = utama.warna;
    el('panel-ikon').setAttribute('href', `#i-${IKON[utama.warna]}`);
    el('panel-status').textContent = utama.status ?? 'Belum ada data';
    el('panel-penanda').textContent = teks.penanda;
    isiBagian('panel-tingkat', teks.tingkat);
    isiBagian('panel-pemicu', utama.pemicu);
    el('panel-saran').textContent = saran(utama);
    isiBagian('panel-lokasi', utama.lokasi, (e) => e.closest('[data-sel]'));
    isiBagian('panel-waktu', utama.waktu ? `${utama.waktu} WIB` : null, (e) => e.closest('[data-sel]'));

    // Ringkasan alat: berapa wilayah dipantau dan berapa alatnya masih mengirim data.
    el('panel-alat').hidden = wilayah.length === 0;
    el('panel-jumlah-wilayah').textContent = `${wilayah.length} wilayah`;
    const aktif = el('panel-jumlah-aktif');
    aktif.classList.toggle('kartu-status__nilai--kurang', offline.length > 0);
    aktif.querySelector('use').setAttribute('href', offline.length > 0 ? '#i-offline' : '#i-alat');
    aktif.querySelector('span').textContent = `${wilayah.length - offline.length} dari ${wilayah.length}`;

    isiBagian('panel-peringatan-teks', teksOffline(offline), () => el('panel-peringatan'));

    if (statusSebelum !== undefined && utama.status !== statusSebelum) {
        const pengumuman = el('pengumuman');
        if (pengumuman) pengumuman.textContent = `Status berubah menjadi ${utama.status ?? 'belum ada data'}. ${saran(utama)}`;
    }
    statusSebelum = utama.status;
}
