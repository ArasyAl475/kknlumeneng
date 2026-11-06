import { Chart, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';

// Hanya bagian yang dipakai, agar unduhan tetap kecil di sinyal lemah.
Chart.register(Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

const WARNA_GARIS = '#164e32';
const angka = (nilai) => Number(nilai).toLocaleString('id-ID', { maximumFractionDigits: 2 });

function labelWaktu(ms, rentangDetik) {
    const tanggal = new Date(ms);
    const opsi = rentangDetik > 2 * 86400
        ? { day: '2-digit', month: 'short' }
        : { hour: '2-digit', minute: '2-digit' };
    return tanggal.toLocaleString('id-ID', opsi);
}

export function buatGrafik(kanvas, { url, pesan, ringkas, wilayah }) {
    let rentangDetik = 86400;
    let permintaanKe = 0;

    const chart = new Chart(kanvas, {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            parsing: false,
            interaction: { mode: 'nearest', axis: 'x', intersect: false },
            scales: {
                x: { type: 'linear', grid: { display: false }, ticks: { maxTicksLimit: 6, color: '#56605a', callback: (v) => labelWaktu(v, rentangDetik) } },
                y: { beginAtZero: true, grid: { color: '#d9d3c6' }, ticks: { color: '#56605a', callback: (v) => angka(v) }, title: { display: true, color: '#56605a' } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { color: '#1e2421', boxWidth: 24, boxHeight: 2 } },
                tooltip: {
                    filter: (item) => item.datasetIndex === 0,
                    callbacks: {
                        title: (items) => (items[0] ? new Date(items[0].parsed.x).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : ''),
                    },
                },
            },
        },
    });

    function tampilkanPesan(teks) {
        pesan.textContent = teks ?? '';
        pesan.classList.toggle('hidden', !teks);
        pesan.classList.toggle('grid', Boolean(teks));
    }

    return {
        async muat(wilayahId, sensor, rentang) {
            if (!wilayahId) {
                tampilkanPesan('Belum ada wilayah yang dipantau.');
                return;
            }

            const ke = ++permintaanKe;
            try {
                const alamat = `${url}?${new URLSearchParams({ wilayah: wilayahId, sensor, rentang })}`;
                const respons = await fetch(alamat, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(15000) });
                if (!respons.ok) throw new Error(`HTTP ${respons.status}`);
                const d = await respons.json();

                // Jawaban lama yang datang terlambat tidak boleh menimpa pilihan terbaru.
                if (ke !== permintaanKe) return;

                rentangDetik = (d.sampai - d.dari) / 1000;
                wilayah.textContent = `· ${d.wilayah}`;

                const garisBatas = (label, nilai, warna, garis) => ({
                    label: `${label} (${angka(nilai)} ${d.satuan})`,
                    data: [{ x: d.dari, y: nilai }, { x: d.sampai, y: nilai }],
                    borderColor: warna,
                    backgroundColor: warna,
                    borderDash: garis,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                });

                chart.data.datasets = [
                    {
                        label: d.sensor,
                        data: d.titik.map((t) => ({ x: t.t, y: t.y })),
                        borderColor: WARNA_GARIS,
                        backgroundColor: WARNA_GARIS,
                        borderWidth: 2,
                        // Satu-dua titik tidak membentuk garis, jadi perlu penanda agar tetap terlihat.
                        pointRadius: d.titik.length < 3 ? 4 : 0,
                        pointHoverRadius: 5,
                        tension: 0,
                    },
                    ...(d.ambang ? [
                        garisBatas('Batas waspada', d.ambang.waspada, '#6b4e00', [8, 5]),
                        garisBatas('Batas perlu validasi', d.ambang.validasi, '#a61b14', [3, 3]),
                    ] : []),
                ];
                chart.options.scales.x.min = d.dari;
                chart.options.scales.x.max = d.sampai;
                chart.options.scales.y.title.text = `${d.sensor} (${d.satuan})`;
                chart.options.scales.y.suggestedMax = d.ambang ? d.ambang.validasi * 1.15 : undefined;
                chart.update();

                const nyata = d.titik.filter((t) => t.y !== null);
                tampilkanPesan(nyata.length ? null : `Belum ada data ${d.sensor.toLowerCase()} pada rentang ini.`);

                if (nyata.length) {
                    const puncak = Math.max(...nyata.map((t) => t.y));
                    const akhir = nyata[nyata.length - 1].y;
                    const teks = `${d.sensor} ${d.wilayah}: terakhir ${angka(akhir)} ${d.satuan}, tertinggi ${angka(puncak)} ${d.satuan} pada rentang ini.`;
                    ringkas.textContent = teks;
                    kanvas.setAttribute('aria-label', teks);
                } else {
                    ringkas.textContent = '';
                }
            } catch {
                if (ke === permintaanKe) tampilkanPesan('Grafik belum bisa dimuat. Akan dicoba lagi otomatis.');
            }
        },
    };
}
