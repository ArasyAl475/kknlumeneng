// Konfirmasi sebelum menghapus, karena materi yang terhapus tidak bisa dipulihkan.
document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});

// Foto yang terlalu besar ditolak sebelum dikirim, agar admin tidak membuang kuota di sinyal lemah.
// Server tetap memeriksa ulang; ini hanya untuk kenyamanan.
document.querySelectorAll('input[type="file"][data-maks-kb]').forEach((input) => {
    const peringatan = document.getElementById(`${input.id}-peringatan`);

    input.addEventListener('change', () => {
        const berkas = input.files[0];
        const terlaluBesar = berkas && berkas.size > Number(input.dataset.maksKb) * 1024;

        peringatan.hidden = !terlaluBesar;
        if (terlaluBesar) input.value = '';
    });
});
