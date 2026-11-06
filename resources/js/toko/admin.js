// Konfirmasi sebelum menghapus. Tanpa JavaScript, aksi tetap berjalan dan tercatat di log aktivitas.
document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});

// Tombol "Tambah baris" hanya muncul bila JavaScript jalan; tanpa itu form sudah menyediakan baris kosong.
document.querySelectorAll('[data-tambah-baris]').forEach((tombol) => {
    const jenis = tombol.dataset.tambahBaris;
    const daftar = document.querySelector(`[data-daftar-baris="${jenis}"]`);
    const cetakan = document.querySelector(`template[data-cetakan="${jenis}"]`);
    if (!daftar || !cetakan) return;

    const MAKS = 20;
    tombol.hidden = false;

    tombol.addEventListener('click', () => {
        const nomor = daftar.children.length;
        if (nomor >= MAKS) return;
        daftar.insertAdjacentHTML('beforeend', cetakan.innerHTML.replaceAll('__i__', String(nomor)));
        daftar.lastElementChild?.querySelector('input')?.focus();
        if (nomor + 1 >= MAKS) tombol.hidden = true;
    });
});
