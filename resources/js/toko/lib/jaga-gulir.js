// Cadangan untuk segar-otomatis.js: bila pindah pilihan terpaksa memuat ulang halaman penuh (mis. sinyal putus
// saat mengambil di belakang layar), posisi gulir dibawa ke halaman berikutnya lewat sessionStorage (hanya tab ini),
// supaya warga tidak dilempar ke pita judul paling atas.

const KUNCI = 'toko.hasil.gulir';

// Lebih lama dari ini dianggap bukan lanjutan klik tadi (mis. tab ditinggal lalu dibuka lagi lewat tautan lain).
const UMUR_MAKS = 60000;

export function pindahPenuh(alamat) {
    try {
        const tujuan = new URL(alamat, location.href).pathname;
        sessionStorage.setItem(KUNCI, JSON.stringify({ alamat: tujuan, y: window.scrollY, waktu: Date.now() }));
    } catch {
        // Penyimpanan diblokir (mode privat): halaman tetap pindah, hanya kembali ke atas.
    }
    location.assign(alamat);
}

export function pulihkanGulir() {
    let simpan = null;
    try {
        simpan = JSON.parse(sessionStorage.getItem(KUNCI) ?? 'null');
        sessionStorage.removeItem(KUNCI);
    } catch {
        return;
    }
    if (!simpan || simpan.alamat !== location.pathname || Date.now() - simpan.waktu > UMUR_MAKS) return;

    // 'instant': app.css memakai scroll-behavior smooth, dan halaman tidak boleh terlihat meluncur dari atas.
    window.scrollTo({ top: simpan.y, behavior: 'instant' });
}
