// Menu Desain Web: pratinjau langsung sebelum disimpan, dan cek format/ukuran sebelum diunggah
// agar admin tidak menunggu unggahan besar yang pasti ditolak (sinyal desa sering lemah).
const FORMAT = ['image/jpeg', 'image/png', 'image/webp'];

const ukuranMb = (bita) => (bita / 1024 / 1024).toFixed(1).replace('.', ',');

document.querySelectorAll('[data-slot]').forEach((kartu) => {
    const input = kartu.querySelector('input[type="file"]');
    const gambar = kartu.querySelector('[data-pratinjau]');
    const lencana = kartu.querySelector('[data-lencana-pratinjau]');
    const namaBerkas = kartu.querySelector('[data-nama-berkas]');
    const galat = kartu.querySelector('[data-galat-berkas]');
    const batal = kartu.querySelector('[data-batal]');
    if (!input || !gambar) return;

    const srcAsli = gambar.src;
    const teksAsli = namaBerkas.textContent;
    const maksKb = Number(input.dataset.maksKb) || 3072;
    let urlSementara = null;

    const kembalikan = () => {
        if (urlSementara) URL.revokeObjectURL(urlSementara);
        urlSementara = null;
        gambar.src = srcAsli;
        lencana.hidden = true;
        batal.hidden = true;
        namaBerkas.textContent = teksAsli;
    };

    const tolak = (pesan) => {
        input.value = '';
        kembalikan();
        galat.textContent = pesan;
        galat.hidden = false;
    };

    input.addEventListener('change', () => {
        galat.hidden = true;
        const berkas = input.files[0];
        if (!berkas) return kembalikan();

        if (!FORMAT.includes(berkas.type)) {
            return tolak('Format tidak didukung. Pilih gambar JPG, PNG, atau WebP.');
        }
        if (berkas.size > maksKb * 1024) {
            return tolak(`Ukuran gambar ${ukuranMb(berkas.size)} MB, lebih dari batas ${ukuranMb(maksKb * 1024)} MB. Pilih gambar yang lebih kecil.`);
        }

        if (urlSementara) URL.revokeObjectURL(urlSementara);
        urlSementara = URL.createObjectURL(berkas);
        gambar.src = urlSementara;
        lencana.hidden = false;
        batal.hidden = false;
        namaBerkas.textContent = `${berkas.name} (${ukuranMb(berkas.size)} MB)`;
    });

    batal.addEventListener('click', () => {
        input.value = '';
        galat.hidden = true;
        kembalikan();
    });
});

// Tombol simpan dikunci setelah ditekan supaya unggahan tidak terkirim dua kali.
document.querySelectorAll('form[data-simpan-slot]').forEach((form) => {
    form.addEventListener('submit', () => {
        const tombol = form.querySelector('[data-tombol-simpan]');
        if (tombol) {
            tombol.disabled = true;
            const teks = tombol.querySelector('[data-teks]');
            if (teks) teks.textContent = 'Menyimpan…';
        }
    });
});

document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});
