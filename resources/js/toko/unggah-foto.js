// Foto kamera HP sering 5–20 MB dan beresolusi sangat tinggi: ditolak server (batas ukuran dan memori)
// dan lama terkirim di sinyal lemah. Di sini browser memperkecil dan mengubahnya ke JPG sebelum diunggah.
// Server tetap memeriksa dan memperkecil ulang; tanpa JavaScript foto dikirim apa adanya.
const SISI_MAKS = 1600; // server memperkecil lagi ke 1200 px, sisanya ruang agar tetap tajam
const MUTU = 0.85;
const BATAS_TANPA_UBAH = 800 * 1024;
const JENIS_LANGSUNG = ['image/jpeg', 'image/png', 'image/webp'];

async function bacaGambar(berkas) {
    try {
        return await createImageBitmap(berkas, { imageOrientation: 'from-image' });
    } catch {
        // Browser lama tidak mengenal opsi orientasi; coba tanpa opsi sebelum menyerah.
        try {
            return await createImageBitmap(berkas);
        } catch {
            return null;
        }
    }
}

async function siapkan(berkas) {
    const gambar = await bacaGambar(berkas);
    // Browser tidak bisa membacanya (misalnya HEIC di Chrome): kirim apa adanya, server yang menjelaskan.
    if (!gambar) return berkas;

    const sisi = Math.max(gambar.width, gambar.height);
    const perluDiubah = !JENIS_LANGSUNG.includes(berkas.type) || berkas.size > BATAS_TANPA_UBAH || sisi > SISI_MAKS;
    if (!perluDiubah) {
        gambar.close();
        return berkas;
    }

    const skala = Math.min(1, SISI_MAKS / sisi);
    const kanvas = document.createElement('canvas');
    kanvas.width = Math.max(1, Math.round(gambar.width * skala));
    kanvas.height = Math.max(1, Math.round(gambar.height * skala));
    const konteks = kanvas.getContext('2d');
    // Latar putih supaya PNG transparan tidak berubah hitam setelah jadi JPG.
    konteks.fillStyle = '#fff';
    konteks.fillRect(0, 0, kanvas.width, kanvas.height);
    konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);
    gambar.close();

    const blob = await new Promise((selesai) => kanvas.toBlob(selesai, 'image/jpeg', MUTU));
    if (!blob) return berkas;

    const nama = (berkas.name.replace(/\.[^.]*$/, '') || 'foto') + '.jpg';
    return new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });
}

function ukuranTeks(bita) {
    return bita >= 1024 * 1024
        ? `${(bita / 1024 / 1024).toFixed(1).replace('.', ',')} MB`
        : `${Math.max(1, Math.round(bita / 1024))} KB`;
}

document.querySelectorAll('form[data-unggah-foto]').forEach((form) => {
    const masukan = form.querySelector('input[type="file"]');
    const status = form.querySelector('[data-status-foto]');
    const tombol = form.querySelector('button[type="submit"]');
    // Tanpa dukungan ini, browser mengirim foto asli dan server yang menanganinya.
    if (!masukan || typeof createImageBitmap !== 'function' || typeof DataTransfer !== 'function') return;

    let sedangMenyiapkan = false;

    const tampilkan = (teks) => {
        if (!status) return;
        status.textContent = teks;
        status.hidden = teks === '';
    };

    masukan.addEventListener('change', async () => {
        const daftar = Array.from(masukan.files ?? []);
        if (daftar.length === 0) return tampilkan('');

        sedangMenyiapkan = true;
        if (tombol) tombol.disabled = true;
        tampilkan(`Menyiapkan ${daftar.length} foto…`);

        try {
            const wadah = new DataTransfer();
            // Satu per satu, bukan sekaligus, agar HP dengan memori kecil tidak kehabisan memori.
            for (const berkas of daftar) {
                wadah.items.add(await siapkan(berkas));
            }
            masukan.files = wadah.files;

            const total = Array.from(wadah.files).reduce((jumlah, b) => jumlah + b.size, 0);
            tampilkan(`${wadah.files.length} foto siap diunggah (${ukuranTeks(total)}). Tekan Unggah foto.`);
        } catch {
            tampilkan('Foto tidak bisa diperkecil otomatis. Foto asli tetap akan dikirim.');
        } finally {
            sedangMenyiapkan = false;
            if (tombol) tombol.disabled = false;
        }
    });

    form.addEventListener('submit', (e) => {
        if (sedangMenyiapkan) e.preventDefault();
    });
});
