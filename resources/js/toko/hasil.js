// Hanya satu rincian tampil agar halaman tidak memanjang di HP.
// Tanpa JavaScript semua rincian tetap terlihat (lihat <noscript> di toko/hasil.blade.php).
const daftarTab = [...document.querySelectorAll('[role="tab"]')];

function pilih(tab, fokus = false) {
    daftarTab.forEach((t) => {
        const aktif = t === tab;
        t.setAttribute('aria-selected', String(aktif));
        t.tabIndex = aktif ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).classList.toggle('hidden', !aktif);
    });
    if (fokus) tab.focus();
}

daftarTab.forEach((tab, i) => {
    tab.addEventListener('click', () => pilih(tab));
    tab.addEventListener('keydown', (e) => {
        const arah = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
        if (!arah) return;
        e.preventDefault();
        pilih(daftarTab[(i + arah + daftarTab.length) % daftarTab.length], true);
    });
});
