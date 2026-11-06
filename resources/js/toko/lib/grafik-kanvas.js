import { BarController, BarElement, CategoryScale, Chart, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip } from 'chart.js';
import { SERI, formatNilai, titikBerdata, urutanAktif } from './grafik-data.js';

// Hanya bagian yang dipakai, agar unduhan tetap kecil di sinyal lemah. Dimuat saat dibutuhkan (import dinamis).
Chart.register(BarController, BarElement, CategoryScale, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip);

// Token warna Stitch: primary #00361f, primary-container #164e32, secondary #006c49, tertiary-fixed-dim #7bd8b1.
const WARNA = { kopi: '#00361f', jamur: '#006c49', terjual: '#7bd8b1', pendapatan: '#164e32', dengan: '#006c49', tanpa: '#141e18' };
// Garis tepi dan garis grafik memakai warna gelap agar tetap jelas di atas latar putih.
const TEPI = { ...WARNA, terjual: '#006c49' };
// Selain warna, tiap seri punya pola isian dan pola garis sendiri.
const POLA = { kopi: 'solid', jamur: 'garis', terjual: 'titik', pendapatan: 'solid', dengan: 'solid', tanpa: 'garis' };
const GARIS_PUTUS = { kopi: [], jamur: [7, 4], terjual: [2, 4], pendapatan: [] };
const REDUP = '#404942';
const GARIS = '#dae5dc';
const JARAK_HURUF = "'Plus Jakarta Sans', ui-sans-serif, system-ui, 'Segoe UI', Roboto, sans-serif";

Chart.defaults.font.family = JARAK_HURUF;
Chart.defaults.font.size = 13;

const ringkasUang = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });
const gerakDikurangi = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function isian(konteks, jenis, warna) {
    if (jenis === 'solid') return warna;
    const bahan = document.createElement('canvas');
    bahan.width = bahan.height = 8;
    const g = bahan.getContext('2d');
    g.fillStyle = warna;
    g.fillRect(0, 0, 8, 8);
    g.fillStyle = 'rgba(255,255,255,0.75)';
    g.strokeStyle = 'rgba(255,255,255,0.75)';
    g.lineWidth = 2;
    if (jenis === 'garis') {
        g.beginPath();
        [[-2, 2, 2, -2], [0, 8, 8, 0], [6, 10, 10, 6]].forEach(([a, b, c, d]) => {
            g.moveTo(a, b);
            g.lineTo(c, d);
        });
        g.stroke();
    } else {
        g.beginPath();
        g.arc(4, 4, 1.4, 0, Math.PI * 2);
        g.fill();
    }

    return konteks.createPattern(bahan, 'repeat');
}

function setelanDasar(skalaData, kanvas) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: gerakDikurangi() ? false : { duration: 200 },
        interaction: { mode: 'index', intersect: false },
        // Tanpa touchmove: menggeser grafik ke samping tidak memunculkan petunjuk yang berkedip.
        events: ['mousemove', 'mouseout', 'click', 'touchstart'],
        layout: { padding: { top: 8, right: 20 } },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#141e18',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                cornerRadius: 12,
                boxWidth: 12,
                boxHeight: 12,
                boxPadding: 4,
                titleFont: { weight: '700', size: 14 },
                bodyFont: { size: 14 },
                callbacks: {
                    title: (item) => skalaData.lengkap[item[0].dataIndex],
                    label: (ctx) => ` ${ctx.dataset.label}: ${formatNilai(ctx.dataset.kunci, ctx.parsed.y)}`,
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                border: { color: GARIS },
                ticks: { color: REDUP, maxRotation: 0, autoSkip: true, autoSkipPadding: 10 },
            },
            y: {
                beginAtZero: true,
                grace: '8%',
                grid: { color: GARIS },
                border: { display: false },
                ticks: { color: REDUP, maxTicksLimit: 5 },
                // Lebar sumbu tetap, supaya grafik kg dan rupiah sejajar menurut waktu.
                afterFit: (sumbu) => { sumbu.width = 68; },
                title: { display: true, text: kanvas === 'rupiah' ? 'Rupiah' : 'Kilogram', color: REDUP },
            },
        },
    };
}

function dataset(konteks, seri, skalaData, garis) {
    const warna = WARNA[seri];
    const dasar = { label: SERI[seri].nama, kunci: seri, data: skalaData[seri] };
    if (garis) {
        return {
            ...dasar,
            type: 'line',
            borderColor: TEPI[seri],
            backgroundColor: `${warna}24`,
            borderDash: GARIS_PUTUS[seri],
            borderWidth: 2,
            fill: 'origin',
            // Garis lurus: lengkungan membuat panen sehari tampak seperti panen beberapa hari.
            tension: 0,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHitRadius: 14,
        };
    }

    return {
        ...dasar,
        type: 'bar',
        backgroundColor: isian(konteks, POLA[seri], warna),
        borderColor: TEPI[seri],
        borderWidth: 1.5,
        borderRadius: 4,
        maxBarThickness: 34,
        categoryPercentage: 0.8,
        barPercentage: 0.92,
    };
}

const NAMA_MEDIA = { dengan: 'Dengan cangkang kopi', tanpa: 'Tanpa cangkang kopi' };
const kgPerBaglog = (n) => `${n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg per baglog`;

/**
 * Batang berkelompok: hasil jamur per baglog tiap bulan, dengan dan tanpa cangkang kopi.
 * Bulan tanpa catatan baglog bernilai null, jadi tidak digambar (bukan batang setinggi nol).
 */
export function gambarBaglog(kanvas, data) {
    const pengaturan = setelanDasar(data, 'kg');
    pengaturan.plugins.tooltip.callbacks.label = (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y === null ? 'belum ada catatan' : kgPerBaglog(ctx.parsed.y)}`;
    pengaturan.scales.y.title.text = 'kg per baglog';
    pengaturan.scales.y.ticks.callback = (v) => v.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const konteks = kanvas.getContext('2d');

    return new Chart(kanvas, {
        type: 'bar',
        data: {
            labels: data.label,
            datasets: ['dengan', 'tanpa'].map((kunci) => ({
                label: NAMA_MEDIA[kunci],
                kunci,
                data: data[kunci],
                type: 'bar',
                backgroundColor: isian(konteks, POLA[kunci], WARNA[kunci]),
                borderColor: TEPI[kunci],
                borderWidth: 1.5,
                borderRadius: 4,
                maxBarThickness: 34,
                categoryPercentage: 0.8,
                barPercentage: 0.92,
            })),
        },
        options: pengaturan,
    });
}

export function buatKanvas() {
    const grafik = { kg: null, rupiah: null };

    function hancurkan(nama) {
        grafik[nama]?.destroy();
        grafik[nama] = null;
    }

    /**
     * Menggambar ulang satu kanvas. Jenis grafik tidak bisa diganti di tempat, jadi grafik lama dibuang.
     * Hari berdata cukup (3 atau lebih) memakai garis; selain itu batang, agar 1-2 titik tidak dipaksa jadi tren.
     */
    function gambar(nama, kanvas, skala, skalaData, aktif) {
        hancurkan(nama);
        const seri = urutanAktif(aktif).filter((s) => SERI[s].kanvas === nama);
        if (!seri.length) return;

        const garis = skala === 'hari' && titikBerdata(skalaData, aktif) >= 3;
        const konteks = kanvas.getContext('2d');
        const pengaturan = setelanDasar(skalaData, nama);
        pengaturan.scales.y.ticks.callback = nama === 'rupiah'
            ? (v) => ringkasUang.format(v)
            : (v) => v.toLocaleString('id-ID', { maximumFractionDigits: 1 });

        grafik[nama] = new Chart(kanvas, {
            type: garis ? 'line' : 'bar',
            data: { labels: skalaData.label, datasets: seri.map((s) => dataset(konteks, s, skalaData, garis)) },
            options: pengaturan,
        });
    }

    return { gambar, hancurkan };
}
