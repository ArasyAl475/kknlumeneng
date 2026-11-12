// Halaman publik Hasil panen dan penjualan. Setiap bagian punya versi tanpa JavaScript
// (tabel bulanan, formulir filter, semua rincian terlihat), jadi skrip ini hanya menambah kenyamanan.
import '../../css/toko-hasil.css';
import { pasangGrafik, pasangGrafikBaglog } from './lib/grafik-halaman.js';
import { pulihkanGulir } from './lib/jaga-gulir.js';
import { pasangLembarFilter } from './lib/lembar-filter.js';
import { pasangSegarOtomatis } from './lib/segar-otomatis.js';

// Paling awal, supaya halaman tidak sempat terlihat di posisi paling atas sebelum dikembalikan.
pulihkanGulir();
pasangLembarFilter();

// Tiap grafik dilacak elemennya: bila masih elemen yang sama, cukup angkanya yang diperbarui (skala dan data
// yang dipilih warga tetap). Bila bloknya diganti utuh (mis. dari "belum ada data" menjadi ada grafik),
// grafik lama dilepas dan yang baru dipasang.
const daftarGrafik = [
    { pilih: '[data-grafik]', pasang: pasangGrafik },
    { pilih: '[data-grafik-baglog]', pasang: pasangGrafikBaglog },
].map((g) => {
    const akar = document.querySelector(g.pilih);
    return { ...g, akar, kendali: akar ? g.pasang(akar) : null };
});

function sesuaikanGrafik(g, dok) {
    const akar = document.querySelector(g.pilih);
    if (akar !== g.akar) {
        g.kendali?.lepas();
        g.akar = akar;
        g.kendali = akar ? g.pasang(akar) : null;
        return;
    }
    const baru = dok.querySelector(g.pilih);
    if (!akar || !baru || akar.dataset.data === baru.dataset.data) return;
    akar.dataset.data = baru.dataset.data;
    g.kendali.perbaruiData(JSON.parse(baru.dataset.data));
}

pasangSegarOtomatis({
    setelahGanti(dok) {
        daftarGrafik.forEach((g) => sesuaikanGrafik(g, dok));
    },
});
