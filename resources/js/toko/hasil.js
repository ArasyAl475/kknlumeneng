// Halaman publik Hasil panen dan penjualan. Setiap bagian punya versi tanpa JavaScript
// (tabel bulanan, formulir filter, semua rincian terlihat), jadi skrip ini hanya menambah kenyamanan.
import '../../css/toko-hasil.css';
import { pasangGrafik, pasangGrafikBaglog } from './lib/grafik-halaman.js';
import { pasangLembarFilter } from './lib/lembar-filter.js';
import { pasangRincian } from './lib/rincian.js';

pasangLembarFilter();
pasangRincian();

const grafik = document.querySelector('[data-grafik]');
if (grafik) pasangGrafik(grafik);

const grafikBaglog = document.querySelector('[data-grafik-baglog]');
if (grafikBaglog) pasangGrafikBaglog(grafikBaglog);
