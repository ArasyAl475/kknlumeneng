// Pembaruan otomatis tanpa memuat ulang halaman. Tiap beberapa detik halaman menanyakan sidik data
// (respons beberapa bita). Bila sidiknya berubah, halaman yang sama diambil di belakang layar lalu hanya
// bagian bertanda yang diganti isinya. Tampilan tetap dibuat Blade di server, jadi tidak ada aturan
// atau format angka yang diduplikasi di JavaScript.
//
//   [data-segar-bagian="kunci"]  isinya diganti (elemennya sendiri tetap, jadi pemantau seperti paginasi tetap jalan).
//   [data-segar-tetap="kunci"]   tidak diganti, mis. grafik yang menyimpan pilihan warga; datanya diurus setelahGanti.
// Bila susunan bagian berbeda (mis. dari "belum ada data" menjadi ada tabel), halaman dimuat ulang biasa.

const JEDA = 5000;
const JEDA_MAKS = 60000;

const susunan = (akar) => [...akar.querySelectorAll('[data-segar-bagian], [data-segar-tetap]')]
    .map((el) => el.dataset.segarBagian ?? `tetap:${el.dataset.segarTetap}`)
    .join('|');

export function pasangSegarOtomatis({ setelahGanti } = {}) {
    const penanda = document.querySelector('[data-segar]');
    if (!penanda || !window.fetch || !window.DOMParser) return;

    const umumkan = penanda.querySelector('[data-segar-umumkan]');
    let versi = penanda.dataset.versi;
    let jeda = JEDA;
    let pewaktu = null;
    let sedang = false;
    let berhenti = false;

    function jadwalkan() {
        clearTimeout(pewaktu);
        if (!berhenti && !document.hidden) pewaktu = setTimeout(periksa, jeda);
    }

    async function ambilHalaman() {
        const respons = await fetch(location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
        if (!respons.ok) throw new Error(String(respons.status));
        // Dialihkan ke halaman lain (mis. sesi admin habis lalu ke halaman masuk): jangan tempel isinya.
        if (new URL(respons.url).pathname !== location.pathname) return null;
        const dok = new DOMParser().parseFromString(await respons.text(), 'text/html');
        return dok.querySelector('[data-segar]') ? dok : null;
    }

    function ganti(dok) {
        const baru = [...dok.querySelectorAll('[data-segar-bagian]')];
        document.querySelectorAll('[data-segar-bagian]').forEach((el, i) => {
            // Lembar filter yang sedang dibuka warga tidak diganggu; disusul perubahan data berikutnya.
            if (el.closest('dialog[open]') || el.innerHTML === baru[i].innerHTML) return;
            el.replaceChildren(...baru[i].childNodes);
        });
    }

    async function periksa() {
        if (sedang || berhenti) return;
        sedang = true;
        try {
            const respons = await fetch(penanda.dataset.alamat, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!respons.ok) throw new Error(String(respons.status));
            const { versi: terbaru } = await respons.json();

            if (terbaru !== versi) {
                const dok = await ambilHalaman();
                if (!dok) {
                    berhenti = true;
                    return;
                }
                if (susunan(dok) !== susunan(document)) {
                    location.reload();
                    return;
                }
                ganti(dok);
                versi = dok.querySelector('[data-segar]').dataset.versi;
                setelahGanti?.(dok);
                const jam = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });
                if (umumkan) umumkan.textContent = `Data terbaru dimuat otomatis pukul ${jam} WIB.`;
            }
            jeda = JEDA;
        } catch {
            // Sinyal putus atau server sibuk: bertanya makin jarang, data terakhir tetap tampil.
            jeda = Math.min(jeda * 2, JEDA_MAKS);
        } finally {
            sedang = false;
        }
        jadwalkan();
    }

    // Tab di latar belakang tidak bertanya (hemat kuota); begitu dibuka lagi langsung diperiksa.
    document.addEventListener('visibilitychange', () => {
        clearTimeout(pewaktu);
        if (!document.hidden) periksa();
    });

    jadwalkan();
}
