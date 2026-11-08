import { useMemo, useState } from 'react';
import { Footer, PageContainer, TopAppBar } from '../components/layout/index.js';
import { HarvestTable, HeroMetricCard, MetricCard, MetricGrid, PageHeader, StatusPill, SubstrateComparison, TimeframeFilter, TrendCard, kolomTimbangan } from '../components/harvest/index.js';
import { Button, Pagination, SegmentedControl, SelectField } from '../components/ui/index.js';
import { filterBlok, filterJenis } from '../data/contohHasil.js';
import { formatAngka, formatKg, formatRupiah } from '../lib/format.js';

const SKALA = [
    { value: '6bulan', label: '6 Bulan' },
    { value: 'tahun', label: 'Tahun Ini' },
    { value: 'semua', label: 'Semua Waktu' },
];

const PER_HALAMAN = 4;

const puncak = (label, nilai) => {
    const i = nilai.indexOf(Math.max(...nilai));
    return { i, label: label[i], nilai: nilai[i] };
};

const persen = (nilai) => (nilai === null ? '' : ` (${nilai}%)`);

/**
 * Halaman "Hasil Panen dan Penjualan Komoditas Desa".
 * Tata letak per-card: setiap kartu angka, grafik, perbandingan, dan tabel adalah SATU card; tidak ada card di dalam card.
 * `data` berbentuk seperti data/contohHasil.js.
 */
export default function HasilPanenPage({ data }) {
    const [periode, setPeriode] = useState('semua');
    const [skala, setSkala] = useState('6bulan');
    const [jenis, setJenis] = useState('all');
    const [blok, setBlok] = useState('all');
    const [halaman, setHalaman] = useState(1);
    const { kartu, perbandingan, grafik, timbangan } = data;

    const deret = grafik?.[skala];
    const adaGrafik = Boolean(deret) && [...deret.kopi, ...deret.jamur].some((n) => n > 0);
    const puncakKopi = adaGrafik ? puncak(deret.label, deret.kopi) : null;
    const puncakJamur = adaGrafik ? puncak(deret.label, deret.jamur) : null;
    const total = adaGrafik ? [...deret.kopi, ...deret.jamur].reduce((a, b) => a + b, 0) : 0;

    const tersaring = useMemo(
        () => timbangan.filter((b) => (jenis === 'all' || b.jenisKunci === jenis) && (blok === 'all' || b.blok === blok)),
        [timbangan, jenis, blok],
    );
    const jumlahHalaman = Math.max(1, Math.ceil(tersaring.length / PER_HALAMAN));
    const halamanAman = Math.min(halaman, jumlahHalaman);
    const tampil = tersaring.slice((halamanAman - 1) * PER_HALAMAN, halamanAman * PER_HALAMAN);

    const ubahFilter = (set) => (nilai) => {
        set(nilai);
        setHalaman(1);
    };

    const kartuAngka = [
        { kunci: 'kopi', label: 'Panen Kopi Rakyat', ikon: 'coffee', ikonKeterangan: 'forest', kosong: 'Belum ada catatan panen kopi' },
        { kunci: 'limbah', label: 'Limbah Kopi (Cangkang)', ikon: 'recycling', ikonKeterangan: 'compost', kosong: 'Belum ada catatan limbah' },
        { kunci: 'jamur', label: 'Panen Jamur', ikon: 'spa', ikonKeterangan: 'warehouse', kosong: 'Belum ada catatan panen jamur' },
        { kunci: 'terjual', label: 'Jamur Terjual', ikon: 'storefront', ikonKeterangan: 'local_shipping', kosong: 'Belum ada penjualan' },
    ];

    return (
        <div className="flex min-h-screen flex-col">
            <TopAppBar
                brand={{ nama: 'Desa Lumeneng', wilayah: 'Kec. Paninggaran, Kab. Pekalongan', ikon: 'nature_people', lencana: 'Agraria Terpadu' }}
                menu={[]}
                peringatan={{ label: 'Peringatan Dini BPBD', href: '#bpbd' }}
            />

            <PageContainer>
                <PageHeader
                    label="Sistem Informasi Agraria Terpadu Desa Lumeneng"
                    judul="Hasil Panen dan Penjualan Komoditas Desa"
                    deskripsi="Portal transparansi publik hasil bumi Desa Lumeneng: memantau perkembangan panen kopi rakyat, inovasi pemanfaatan cangkang kopi, dan produksi jamur tiram warga."
                >
                    <StatusPill>Transparansi Terbuka untuk Warga</StatusPill>
                    <TimeframeFilter value={periode} onChange={setPeriode} />
                </PageHeader>

                <MetricGrid>
                    <HeroMetricCard
                        className="sm:col-span-2 lg:col-span-1"
                        label="Uang dari Jamur Terjual"
                        nilai={formatRupiah(kartu.uang.nilai)}
                        badge={kartu.uang.badge}
                        deskripsi={kartu.uang.deskripsi}
                    />
                    {kartuAngka.map((k) => (
                        <MetricCard
                            key={k.kunci}
                            label={k.label}
                            ikon={k.ikon}
                            nilai={formatAngka(kartu[k.kunci].nilai)}
                            satuan="kg"
                            kosong={kartu[k.kunci].nilai <= 0}
                            keterangan={kartu[k.kunci].nilai > 0 ? kartu[k.kunci].keterangan : k.kosong}
                            ikonKeterangan={k.ikonKeterangan}
                            kaki={kartu[k.kunci].nilai > 0 ? kartu[k.kunci].kaki : undefined}
                        />
                    ))}
                </MetricGrid>

                <section className="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                    <TrendCard
                        className="lg:col-span-7"
                        judul="Perkembangan Hasil Desa"
                        deskripsi="Dinamika volume panen komoditas unggulan"
                        skala={{ pilihan: SKALA, value: skala, onChange: setSkala }}
                        kosong={
                            adaGrafik
                                ? null
                                : {
                                      judul: 'Belum ada data untuk periode ini',
                                      deskripsi: 'Grafik muncul setelah admin desa mencatat panen atau penjualan.',
                                      aksi: (
                                          <Button variant="solid" onClick={() => setSkala('semua')}>
                                              Lihat semua waktu
                                          </Button>
                                      ),
                                  }
                        }
                        banner={
                            adaGrafik && (
                                <>
                                    <span className="font-bold text-primary">
                                        Puncak Panen Kopi: {puncakKopi.label} ({formatKg(puncakKopi.nilai)})
                                    </span>
                                    <span className="mx-1.5 text-outline" aria-hidden="true">
                                        •
                                    </span>
                                    <span className="font-bold text-secondary">
                                        Jamur: {puncakJamur.label} ({formatKg(puncakJamur.nilai)})
                                    </span>
                                </>
                            )
                        }
                        grafik={
                            adaGrafik && {
                                labels: deret.label,
                                satuan: 'kg',
                                sorot: puncakKopi.i,
                                format: formatAngka,
                                seri: [
                                    { kunci: 'kopi', nama: 'Panen Kopi Rakyat', nilai: deret.kopi, kelas: 'bg-primary' },
                                    { kunci: 'jamur', nama: 'Panen Jamur Tiram', nilai: deret.jamur, kelas: 'bg-secondary-container' },
                                ],
                            }
                        }
                        ringkasan={
                            adaGrafik && [
                                { label: 'Total Hasil', nilai: formatAngka(total, 0), satuan: 'kg' },
                                { label: 'Rata-rata Bulanan', nilai: formatAngka(total / deret.label.length, 0), satuan: 'kg/bln' },
                                { label: 'Bulan Tertinggi', nilai: puncakKopi.label, tone: 'positif' },
                            ]
                        }
                    />

                    <SubstrateComparison
                        className="lg:col-span-5"
                        lencana="Riset Sirkular KKN & Aksara Peduli"
                        judul="Apakah cangkang kopi membantu jamur?"
                        deskripsi="Hasil jamur per baglog (kantong media tanam), dibandingkan antara yang dicampur cangkang kopi dan tanpa cangkang."
                        cukup={perbandingan.cukup}
                        kesimpulan={perbandingan.kalimat}
                        dengan={{
                            judul: 'Dengan cangkang kopi',
                            lencana: 'Substrat Eksperimen',
                            baris: [
                                { label: 'Jumlah baglog', nilai: `${formatAngka(perbandingan.dengan.baglog, 0)} log` },
                                { label: 'Total panen jamur', nilai: formatKg(perbandingan.dengan.panen) },
                                { label: 'Hasil per baglog', nilai: perbandingan.dengan.perBaglog === null ? 'Belum ada data' : `${formatAngka(perbandingan.dengan.perBaglog)} kg/log`, tebal: true },
                                { label: 'Jamur terjual', nilai: `${formatKg(perbandingan.dengan.terjual)}${persen(perbandingan.dengan.persenTerjual)}` },
                            ],
                        }}
                        tanpa={{
                            judul: 'Tanpa cangkang kopi',
                            lencana: 'Kontrol Konvensional',
                            baris: [
                                { label: 'Jumlah baglog', nilai: `${formatAngka(perbandingan.tanpa.baglog, 0)} log` },
                                { label: 'Total panen jamur', nilai: formatKg(perbandingan.tanpa.panen) },
                                { label: 'Hasil per baglog', nilai: perbandingan.tanpa.perBaglog === null ? 'Belum ada data' : `${formatAngka(perbandingan.tanpa.perBaglog)} kg/log` },
                                { label: 'Jamur terjual', nilai: `${formatKg(perbandingan.tanpa.terjual)}${persen(perbandingan.tanpa.persenTerjual)}` },
                            ],
                        }}
                        catatan={perbandingan.cukup ? 'Data pengamatan aktif - Menunggu siklus petik berikutnya' : undefined}
                    />
                </section>

                <HarvestTable
                    judul="Transparansi Timbangan Hasil Bumi Warga"
                    lencana="Terbuka untuk Publik"
                    deskripsi="Data penerimaan dan penimbangan panen kopi rakyat yang diserahkan warga di Balai Desa Lumeneng"
                    kontrol={
                        <>
                            <SelectField id="filter-jenis" label="Jenis:" value={jenis} onChange={ubahFilter(setJenis)} options={filterJenis} />
                            <SelectField id="filter-blok" label="Blok:" value={blok} onChange={ubahFilter(setBlok)} options={filterBlok} />
                            <Button icon="download">Export CSV / Cetak Laporan</Button>
                        </>
                    }
                    kolom={kolomTimbangan}
                    baris={tampil}
                    kosong={{ judul: 'Belum ada catatan penimbangan pada pilihan ini', deskripsi: 'Coba pilih Semua Jenis atau Semua Blok Kebun.' }}
                    kaki={
                        tersaring.length > 0 && (
                            <>
                                <div>
                                    Menampilkan {tampil.length} dari {tersaring.length} catatan penimbangan kopi
                                </div>
                                <Pagination halaman={halamanAman} jumlahHalaman={jumlahHalaman} onChange={setHalaman} />
                            </>
                        )
                    }
                />
            </PageContainer>

            <Footer
                nama="Desa Lumeneng"
                hakCipta="© 2025 Pemerintah Desa Lumeneng, Kecamatan Paninggaran, Kabupaten Pekalongan. Didukung Sistem Cerdas Agraria."
                tautan={[
                    { label: 'Panduan Penggunaan', href: '#panduan' },
                    { label: 'Kebijakan Data Tani', href: '#kebijakan' },
                    { label: 'Pusat Informasi BPBD', href: '#bpbd' },
                    { label: 'Kontak Balai Desa', href: '#kontak' },
                ]}
                catatan="Penting: informasi peringatan dini di situs ini adalah alat bantu, bukan peringatan resmi BPBD. Saat kondisi berbahaya, segera menjauh dan hubungi perangkat desa."
            />
        </div>
    );
}
