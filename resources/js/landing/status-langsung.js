// Status longsor di beranda ikut berubah sendiri (kartu "Kondisi lereng" dan pil di hero), memakai
// sumber data dan kartu yang sama dengan dasbor EWS. Teks saran beranda dikirim dari halaman
// (data-saran) supaya kalimatnya tetap sama dengan yang tampil saat halaman pertama dimuat.
import { IKON, tampilkanPanel } from '../ews/lib/panel.js';
import { mulaiPembaruan } from '../ews/lib/pembaruan.js';

const akar = document.getElementById('status-langsung');

if (akar && document.getElementById('panel')) {
    const saran = JSON.parse(akar.dataset.saran);
    const chip = document.getElementById('chip-status');

    const tampilkan = (data) => {
        const { utama } = data;

        tampilkanPanel(data, {
            saran: (u) => saran[u.warna] ?? u.saran,
            teksOffline: (offline) => {
                if (offline.length === 0) return '';
                return offline.some((a) => a.saat_waspada) ? akar.dataset.teksOfflineWaspada : akar.dataset.teksOffline;
            },
        });

        if (chip) {
            chip.dataset.warna = utama.warna;
            chip.querySelector('use').setAttribute('href', `#i-${IKON[utama.warna]}`);
            chip.querySelector('[data-isi="chip-teks"]').textContent = `Status longsor: ${utama.status ?? 'Belum ada data'}`;
        }
    };

    mulaiPembaruan(akar.dataset.urlData, { saatData: tampilkan });
}
