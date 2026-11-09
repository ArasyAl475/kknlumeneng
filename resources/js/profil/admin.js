import { kecilkanFoto } from './lib/kecilkan-foto.js';

// Konfirmasi sebelum menghapus, karena data yang terhapus tidak bisa dipulihkan.
document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});

// Foto dikecilkan dulu, lalu yang masih terlalu besar ditolak sebelum dikirim, agar admin tidak
// membuang kuota di sinyal lemah. Server tetap memeriksa ulang; ini hanya untuk kenyamanan.
document.querySelectorAll('input[type="file"][data-maks-kb]').forEach((input) => {
    const peringatan = document.getElementById(`${input.id}-peringatan`);
    const status = document.getElementById(`${input.id}-status`);
    const pratinjau = document.getElementById(`${input.id}-pratinjau`);
    const form = input.form;
    let sedangMenyiapkan = false;

    input.addEventListener('change', async () => {
        const asli = [...input.files];
        if (!asli.length) {
            tampilkanPratinjau([]);
            return;
        }

        sedangMenyiapkan = true;
        if (status) status.textContent = 'Menyiapkan foto…';

        const hasil = await Promise.all(asli.map(kecilkanFoto));
        const batas = Number(input.dataset.maksKb) * 1024;
        const lolos = hasil.filter((berkas) => berkas.size <= batas);

        try {
            const wadah = new DataTransfer();
            lolos.forEach((berkas) => wadah.items.add(berkas));
            input.files = wadah.files;
        } catch {
            // Browser tanpa DataTransfer: berkas asli tetap dikirim dan diperiksa server.
        }

        if (peringatan) peringatan.hidden = lolos.length === hasil.length;
        if (status) status.textContent = lolos.length ? `${lolos.length} foto siap disimpan.` : '';
        tampilkanPratinjau(lolos);
        sedangMenyiapkan = false;
    });

    // Jangan kirim saat foto masih dikecilkan, agar yang terkirim bukan berkas asli yang besar.
    form?.addEventListener('submit', (e) => {
        if (sedangMenyiapkan) {
            e.preventDefault();
            if (status) status.textContent = 'Tunggu sebentar, foto masih disiapkan…';
        }
    });

    function tampilkanPratinjau(daftar) {
        if (!pratinjau) return;
        pratinjau.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
        pratinjau.replaceChildren(
            ...daftar.map((berkas) => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(berkas);
                img.alt = '';
                img.className = 'aspect-square w-full rounded-lg object-cover';
                return img;
            }),
        );
    }
});
