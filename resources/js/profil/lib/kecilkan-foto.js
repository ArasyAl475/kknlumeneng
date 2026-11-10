// Foto kamera HP bisa 3–8 MB. Dikecilkan di HP admin sebelum dikirim agar unggahan cepat di
// sinyal lemah dan tidak melewati batas server. Server tetap menulis ulang foto (lihat PenyimpanGambar).
const SISI_TERPANJANG = 1600;
const MUTU = 0.85;
const PERLU_DIKECILKAN = 1024 * 1024; // di bawah 1 MB dikirim apa adanya

export async function kecilkanFoto(berkas) {
    if (!berkas.type.startsWith('image/') || berkas.size < PERLU_DIKECILKAN || !window.createImageBitmap) {
        return berkas;
    }

    try {
        const gambar = await createImageBitmap(berkas, { imageOrientation: 'from-image' });
        const skala = Math.min(1, SISI_TERPANJANG / Math.max(gambar.width, gambar.height));
        const kanvas = document.createElement('canvas');
        kanvas.width = Math.round(gambar.width * skala);
        kanvas.height = Math.round(gambar.height * skala);

        const konteks = kanvas.getContext('2d');
        // Latar putih agar bagian transparan PNG tidak menjadi hitam di JPEG.
        konteks.fillStyle = '#fff';
        konteks.fillRect(0, 0, kanvas.width, kanvas.height);
        konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);
        gambar.close?.();

        const blob = await new Promise((selesai) => kanvas.toBlob(selesai, 'image/jpeg', MUTU));
        if (!blob || blob.size >= berkas.size) return berkas;

        const nama = berkas.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });
    } catch {
        // Browser lama atau format yang tidak dikenal: kirim berkas asli, server yang mengecilkan.
        return berkas;
    }
}
