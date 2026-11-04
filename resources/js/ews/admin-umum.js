// Konfirmasi sebelum aksi penting. Tanpa JavaScript, aksi tetap berjalan dan tercatat di log aktivitas.
document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});

// Tombol isi cepat di simulator.
document.querySelectorAll('[data-isi-cepat]').forEach((tombol) => {
    tombol.addEventListener('click', () => {
        Object.entries(JSON.parse(tombol.dataset.isiCepat)).forEach(([nama, nilai]) => {
            document.getElementById(nama).value = nilai;
        });
        document.getElementById('kabel_putus').checked = false;
    });
});
