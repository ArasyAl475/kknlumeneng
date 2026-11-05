// Konfirmasi sebelum menghapus catatan, karena catatan yang dihapus tidak bisa dipulihkan.
document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
});
