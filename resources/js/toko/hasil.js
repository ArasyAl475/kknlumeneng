// Halaman publik Hasil panen dan penjualan. Setiap bagian punya versi tanpa JavaScript
// (tabel bulanan, formulir filter, semua rincian terlihat), jadi skrip ini hanya menambah kenyamanan.
import '../../css/toko-hasil.css';
import { pasangGrafik, pasangGrafikBaglog } from './lib/grafik-halaman.js';
import { pasangLembarFilter } from './lib/lembar-filter.js';
import { pasangSegarOtomatis } from './lib/segar-otomatis.js';

pasangLembarFilter();

const grafik = document.querySelector('[data-grafik]');
const kendaliGrafik = grafik ? pasangGrafik(grafik) : null;

const grafikBaglog = document.querySelector('[data-grafik-baglog]');
const kendaliBaglog = grafikBaglog ? pasangGrafikBaglog(grafikBaglog) : null;

// Grafik tidak diganti utuh supaya skala dan data yang dipilih warga tetap; cukup angkanya yang diperbarui.
function salinData(akar, kendali, baru) {
    if (!akar || !baru || akar.dataset.data === baru.dataset.data) return;
    akar.dataset.data = baru.dataset.data;
    kendali.perbaruiData(JSON.parse(baru.dataset.data));
}

pasangSegarOtomatis({
    setelahGanti(dok) {
        salinData(grafik, kendaliGrafik, dok.querySelector('[data-grafik]'));
        salinData(grafikBaglog, kendaliBaglog, dok.querySelector('[data-grafik-baglog]'));
    },
});
