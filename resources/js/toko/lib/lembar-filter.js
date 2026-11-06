// Lembar filter memakai <dialog> bawaan peramban: fokus terkunci di dalamnya, Esc menutup,
// dan pembaca layar mengenalnya sebagai jendela. Tanpa JavaScript, formulir tampil biasa (lihat <noscript>).
export function pasangLembarFilter() {
    const lembar = document.querySelector('[data-lembar]');
    const tombolBuka = document.querySelector('[data-buka-filter]');
    if (!lembar || !tombolBuka || typeof lembar.showModal !== 'function') return;

    tombolBuka.removeAttribute('hidden');
    tombolBuka.addEventListener('click', () => lembar.showModal());
    lembar.querySelector('[data-tutup-filter]').addEventListener('click', () => lembar.close());
    // Sentuhan pada tirai (di luar lembar) menutup lembar.
    lembar.addEventListener('click', (e) => {
        if (e.target === lembar) lembar.close();
    });
}
