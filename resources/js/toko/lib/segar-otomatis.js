// Pembaruan tanpa memuat ulang halaman. Tiap beberapa detik halaman menanyakan sidik data (respons beberapa bita).
// Bila sidiknya berubah, halaman yang sama diambil di belakang layar lalu hanya bagian bertanda yang diganti isinya.
// Tombol pilihan (waktu, filter) memakai cara yang sama untuk alamat baru, jadi halaman tidak berkedip putih dan
// tidak melompat ke atas. Tampilan tetap dibuat Blade di server, jadi tidak ada aturan atau format angka
// yang diduplikasi di JavaScript.
//
//   [data-segar-bagian="kunci"]  isinya diganti (elemennya sendiri tetap, jadi pemantau seperti paginasi tetap jalan).
//   [data-segar-tetap="kunci"]   tidak diganti, mis. grafik yang menyimpan pilihan warga; datanya diurus setelahGanti.
//   [data-segar-blok="kunci"]    wilayah yang isinya bisa berganti bentuk (mis. "belum ada data" menjadi tabel):
//                                bila susunan di dalamnya berbeda, blok diganti utuh dan event `segar:pasang` dikirim
//                                ke blok baru supaya skrip lain (paginasi, grafik) bisa memasang dirinya.
//   a[data-segar-pindah], form[data-segar-pindah]   pindah alamat di tempat (form hanya GET).
// Bila susunan di luar blok berbeda, halaman dimuat ulang biasa.

import { pindahPenuh } from './jaga-gulir.js';

const JEDA = 5000;
const JEDA_MAKS = 60000;
const PENANDA = '[data-segar-bagian], [data-segar-tetap], [data-segar-blok]';

const kunci = (el) => (el.dataset.segarBlok !== undefined ? `blok:${el.dataset.segarBlok}`
    : el.dataset.segarBagian ?? `tetap:${el.dataset.segarTetap}`);

// Penanda milik akar langsung: yang berada di dalam blok lain diurus oleh blok itu sendiri.
const penandaDari = (akar) => {
    const pemilik = akar instanceof Element ? akar : null;
    return [...akar.querySelectorAll(PENANDA)].filter((el) => el.parentElement.closest('[data-segar-blok]') === pemilik);
};

const alamatDari = (url) => url.pathname + url.search;

const susunan = (akar) => penandaDari(akar).map(kunci).join('|');

export function pasangSegarOtomatis({ setelahGanti } = {}) {
    const penanda = document.querySelector('[data-segar]');
    if (!penanda || !window.fetch || !window.DOMParser) return;

    const umumkan = penanda.querySelector('[data-segar-umumkan]');
    let versi = penanda.dataset.versi;
    let jeda = JEDA;
    let pewaktu = null;
    let sedang = false;
    let berhenti = false;
    // Hanya hasil permintaan terakhir yang dipasang, supaya klik cepat beruntun atau pembaruan berkala
    // yang terlambat tidak menimpa pilihan yang lebih baru.
    let giliran = 0;
    let alamatTampil = alamatDari(location);

    function jadwalkan() {
        clearTimeout(pewaktu);
        if (!berhenti && !document.hidden) pewaktu = setTimeout(periksa, jeda);
    }

    async function ambilHalaman(alamat) {
        const respons = await fetch(alamat, { headers: { Accept: 'text/html' }, cache: 'no-store' });
        if (!respons.ok) throw new Error(String(respons.status));
        // Dialihkan ke halaman lain (mis. sesi admin habis lalu ke halaman masuk): jangan tempel isinya.
        if (new URL(respons.url).pathname !== new URL(alamat, location.href).pathname) return null;
        const dok = new DOMParser().parseFromString(await respons.text(), 'text/html');
        return dok.querySelector('[data-segar]') ? dok : null;
    }

    function ganti(lama, baru) {
        const daftarBaru = penandaDari(baru);
        penandaDari(lama).forEach((el, i) => {
            const n = daftarBaru[i];
            if (el.dataset.segarBlok !== undefined) {
                if (susunan(el) === susunan(n)) {
                    ganti(el, n);
                } else {
                    el.replaceWith(n);
                    n.dispatchEvent(new CustomEvent('segar:pasang', { bubbles: true }));
                }
            } else if (el.dataset.segarBagian !== undefined) {
                // Lembar filter yang sedang dibuka warga tidak diganggu; disusul perubahan data berikutnya.
                if (el.closest('dialog[open]') || el.innerHTML === n.innerHTML) return;
                el.replaceChildren(...n.childNodes);
            }
        });
    }

    // Tombol yang diklik ikut terganti; tanpa ini fokus papan ketik/pembaca layar terlempar ke awal halaman.
    function ingatFokus() {
        const aktif = document.activeElement;
        const bagian = aktif?.closest?.('[data-segar-bagian]');
        if (!bagian) return () => {};
        const kunciBagian = bagian.dataset.segarBagian;
        const urutan = [...bagian.querySelectorAll('a, button, input, select, textarea')].indexOf(aktif);

        return () => {
            if (document.activeElement && document.activeElement !== document.body) return;
            const pengganti = document.querySelector(`[data-segar-bagian="${CSS.escape(kunciBagian)}"]`)
                ?.querySelectorAll('a, button, input, select, textarea')[urutan];
            pengganti?.focus({ preventScroll: true });
        };
    }

    function pasang(dok) {
        const kembalikanFokus = ingatFokus();
        ganti(document, dok);
        versi = dok.querySelector('[data-segar]').dataset.versi;
        setelahGanti?.(dok);
        kembalikanFokus();
    }

    async function periksa() {
        if (sedang || berhenti) return;
        sedang = true;
        try {
            const respons = await fetch(penanda.dataset.alamat, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!respons.ok) throw new Error(String(respons.status));
            const { versi: terbaru } = await respons.json();

            const nomor = terbaru !== versi ? ++giliran : 0;
            const dok = nomor ? await ambilHalaman(location.href) : null;
            // Warga baru saja memilih tampilan lain: hasil ini sudah basi, dan pilihan itu membawa data terbaru.
            if (nomor && nomor === giliran) {
                if (!dok) {
                    berhenti = true;
                    return;
                }
                if (susunan(dok) !== susunan(document)) {
                    location.reload();
                    return;
                }
                pasang(dok);
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

    async function pindah(alamat, { catat = true } = {}) {
        const nomor = ++giliran;
        // Di sinyal lemah data lama tetap tampil (sedikit pudar lewat CSS) sampai yang baru siap, bukan layar kosong.
        document.documentElement.setAttribute('aria-busy', 'true');
        try {
            const dok = await ambilHalaman(alamat);
            if (nomor !== giliran) return;
            if (!dok || susunan(dok) !== susunan(document)) {
                pindahPenuh(alamat);
                return;
            }
            document.querySelector('dialog[open]')?.close();
            if (catat) history.pushState({ segar: true }, '', alamat);
            alamatTampil = alamatDari(location);
            document.title = dok.title;
            pasang(dok);
            if (umumkan) {
                const ringkas = document.querySelector('[data-segar-ringkas]')?.textContent.trim();
                umumkan.textContent = ringkas || 'Tampilan diperbarui.';
            }
        } catch {
            // Gagal mengambil di belakang layar: serahkan ke browser, yang juga menampilkan pesan galat bila sinyal putus.
            if (nomor === giliran) pindahPenuh(alamat);
        } finally {
            if (nomor === giliran) document.documentElement.removeAttribute('aria-busy');
        }
    }

    document.addEventListener('click', (e) => {
        const tautan = e.target.closest?.('a[data-segar-pindah]');
        // Klik dengan Ctrl/tombol tengah membuka tab baru seperti biasa.
        if (!tautan || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        pindah(tautan.href);
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || !form.matches?.('form[data-segar-pindah]') || form.method.toLowerCase() !== 'get') return;
        e.preventDefault();
        const alamat = new URL(form.action, location.href);
        alamat.search = new URLSearchParams(new FormData(form)).toString();
        pindah(alamat.href);
    });

    // Tombol Kembali/Maju browser: alamat sudah berganti, isinya disusulkan di tempat.
    // Pindah ke #jangkar di halaman yang sama (mis. "Lompat ke isi") juga memicu popstate; itu dilewati.
    window.addEventListener('popstate', () => {
        if (alamatDari(location) !== alamatTampil) pindah(location.href, { catat: false });
    });

    // Tab di latar belakang tidak bertanya (hemat kuota); begitu dibuka lagi langsung diperiksa.
    document.addEventListener('visibilitychange', () => {
        clearTimeout(pewaktu);
        if (!document.hidden) periksa();
    });

    jadwalkan();
}
