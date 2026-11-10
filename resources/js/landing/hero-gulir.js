// Teks hero memudar ke atas saat halaman digulir. Skrip ini hanya mengukur seberapa jauh hero
// sudah digulir (--p, 0 sampai 1); bentuk geraknya dihitung CSS di app.css (.hero-geser).
// Tanpa JavaScript, atau dengan "kurangi gerakan" di perangkat, teks hero diam seperti biasa.
const hero = document.querySelector('[data-hero-gulir]');
const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');

if (hero && !kurangiGerak.matches) {
    // Selesai memudar saat hero sudah tergulir sekitar 70% tingginya.
    let jarak = 1;
    let terakhir = -1;
    let tunggu = 0;

    const ukur = () => {
        jarak = Math.max(280, hero.offsetHeight * 0.7);
        perbarui();
    };

    function perbarui() {
        tunggu = 0;
        const p = Math.min(1, Math.max(0, window.scrollY / jarak));
        if (p === terakhir) return;
        terakhir = p;
        hero.style.setProperty('--p', p.toFixed(3));
        hero.toggleAttribute('data-bergerak', p > 0);
        hero.toggleAttribute('data-selesai', p >= 0.98);
    }

    window.addEventListener('scroll', () => {
        if (!tunggu) tunggu = requestAnimationFrame(perbarui);
    }, { passive: true });
    window.addEventListener('resize', ukur);
    // Halaman dimuat ulang di tengah gulir: langsung tampil sesuai posisi, tanpa lompatan.
    ukur();
}
