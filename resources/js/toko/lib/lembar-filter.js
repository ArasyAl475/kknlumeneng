// Lembar filter memakai <dialog> bawaan peramban: fokus terkunci di dalamnya, Esc menutup,
// dan pembaca layar mengenalnya sebagai jendela. Tanpa JavaScript, formulir tampil biasa (lihat <noscript>).
export function pasangLembarFilter() {
    const lembar = document.querySelector('[data-lembar]');
    const tombolBuka = document.querySelector('[data-buka-filter]');
    if (!lembar || !tombolBuka) return;
    // Tombol sudah tampil sejak HTML dimuat (agar tidak menggeser tata letak); sembunyikan hanya bila peramban tak mendukung.
    if (typeof lembar.showModal !== 'function') {
        tombolBuka.hidden = true;
        return;
    }

    tombolBuka.addEventListener('click', () => lembar.showModal());
    lembar.querySelector('[data-tutup-filter]').addEventListener('click', () => lembar.close());
    // Sentuhan pada tirai (di luar lembar) menutup lembar.
    lembar.addEventListener('click', (e) => {
        if (e.target === lembar) lembar.close();
    });
}
