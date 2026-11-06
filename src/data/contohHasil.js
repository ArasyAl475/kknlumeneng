// Data contoh untuk pratinjau, mengikuti isi spesifikasi Stitch (grafik Mei-Okt, perbandingan media tanam,
// tabel timbangan). Semua angka dan nama REKAAN, jangan dipakai sebagai data desa.
// Teks kartu seperti "Target Musim" dan "Daya Serap BUMDes" juga placeholder dari Stitch; tabel Laravel saat ini
// (toko_panen_kopi) belum punya kolom petani, blok, potongan air, maupun status validasi.

const bulan6 = ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'];
const kopi6 = [620, 710, 780, 830, 870, 920];
const jamur6 = [210, 260, 310, 350, 380, 410];

const bulan10 = ['Jan', 'Feb', 'Mar', 'Apr', ...bulan6];
const kopi10 = [0, 0, 120, 340, ...kopi6];
const jamur10 = [160, 150, 170, 190, ...jamur6];

const timbangan = [
    { id: 1, tanggal: '24 Okt 2024', petani: 'Slamet Riyadi (KT Tani Maju)', blok: 'Blok Hutan Sigaluh', jenisKunci: 'robusta', jenis: 'Robusta', kotor: 125.5, potongan: 5.5, bersih: 120, status: 'Terverifikasi', statusIkon: 'check_circle' },
    { id: 2, tanggal: '24 Okt 2024', petani: 'Bahrul Ulum', blok: 'Blok Curug Kali Genting', jenisKunci: 'arabika', jenis: 'Arabika', kotor: 98, potongan: 3, bersih: 95, status: 'Terverifikasi', statusIkon: 'check_circle' },
    { id: 3, tanggal: '23 Okt 2024', petani: 'Koperasi Rimba Asri', blok: 'Blok Puncak Wadas', jenisKunci: 'robusta', jenis: 'Robusta', kotor: 310, potongan: 10, bersih: 300, status: 'Terverifikasi', statusIkon: 'check_circle' },
    { id: 4, tanggal: '22 Okt 2024', petani: 'Sugito (Blok Barat)', blok: 'Blok Curug Kali Genting', jenisKunci: 'robusta', jenis: 'Robusta', kotor: 74.2, potongan: 2.2, bersih: 72, status: 'Tercatat Resmi', statusIkon: 'verified' },
    { id: 5, tanggal: '21 Okt 2024', petani: 'Mulyani', blok: 'Blok Hutan Sigaluh', jenisKunci: 'arabika', jenis: 'Arabika', kotor: 64, potongan: 2, bersih: 62, status: 'Terverifikasi', statusIkon: 'check_circle' },
    { id: 6, tanggal: '20 Okt 2024', petani: 'KT Tani Maju', blok: 'Blok Puncak Wadas', jenisKunci: 'robusta', jenis: 'Robusta', kotor: 210.4, potongan: 6.4, bersih: 204, status: 'Terverifikasi', statusIkon: 'check_circle' },
];

export const filterJenis = [
    { value: 'all', label: 'Semua Jenis' },
    { value: 'robusta', label: 'Robusta Menoreh Paninggaran' },
    { value: 'arabika', label: 'Arabika Lereng Ragajambangan' },
];

export const filterBlok = [
    { value: 'all', label: 'Semua Blok Kebun' },
    { value: 'Blok Hutan Sigaluh', label: 'Blok Hutan Sigaluh' },
    { value: 'Blok Curug Kali Genting', label: 'Blok Curug Kali Genting' },
    { value: 'Blok Puncak Wadas', label: 'Blok Puncak Wadas' },
];

export const dataContoh = {
    kartu: {
        uang: { nilai: 18750000, badge: 'Akumulasi Total', deskripsi: 'Realisasi kas penjualan kelompok tani jamur untuk kas desa & warga.' },
        kopi: { nilai: 4730, keterangan: 'Perkebunan Warga Blok Hutan', kaki: { kiri: 'Target Musim: 3.500 kg', kanan: 'Aktif' } },
        limbah: { nilai: 1324.4, keterangan: 'Substrat Baglog Potensial', kaki: { kiri: 'Rasio Konversi: ~28%', kanan: 'Tersimpan' } },
        jamur: { nilai: 1920, keterangan: 'Kumbung Budidaya Desa', kaki: { kiri: 'Kapasitas Kumbung: 1.200 log', kanan: 'Mekar' } },
        terjual: { nilai: 1795.5, keterangan: 'Realisasi Distribusi Pasar', kaki: { kiri: 'Daya Serap BUMDes: 100%', kanan: 'Terkirim' } },
    },
    grafik: {
        '6bulan': { label: bulan6, kopi: kopi6, jamur: jamur6 },
        tahun: { label: bulan10, kopi: kopi10, jamur: jamur10 },
        semua: { label: bulan10, kopi: kopi10, jamur: jamur10 },
    },
    perbandingan: {
        cukup: true,
        kalimat: 'Baglog dengan cangkang kopi menghasilkan 28% lebih banyak jamur per baglog daripada tanpa cangkang kopi.',
        dengan: { baglog: 600, panen: 278.5, perBaglog: 0.46, terjual: 265, persenTerjual: 95 },
        tanpa: { baglog: 600, panen: 216, perBaglog: 0.36, terjual: 200, persenTerjual: 92 },
    },
    timbangan,
};

// Keadaan kosong: tidak ada catatan sama sekali.
export const dataKosong = {
    kartu: {
        uang: { nilai: 0, badge: 'Akumulasi Total', deskripsi: 'Realisasi kas penjualan kelompok tani jamur untuk kas desa & warga.' },
        kopi: { nilai: 0 },
        limbah: { nilai: 0 },
        jamur: { nilai: 0 },
        terjual: { nilai: 0 },
    },
    grafik: null,
    perbandingan: {
        cukup: false,
        kalimat: 'Data belum cukup untuk dibandingkan.',
        dengan: { baglog: 0, panen: 0, perBaglog: null, terjual: 0, persenTerjual: null },
        tanpa: { baglog: 0, panen: 0, perBaglog: null, terjual: 0, persenTerjual: null },
    },
    timbangan: [],
};
