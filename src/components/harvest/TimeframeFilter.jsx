import { SegmentedControl } from '../ui/index.js';

/**
 * Pilihan waktu: Semua waktu | Tahun ini | Bulan ini | Kustom.
 * Pilihan sama berlaku untuk semua bagian halaman (ringkasan, grafik, perbandingan, rincian).
 */
const PILIHAN = [
    { value: 'semua', label: 'Semua waktu' },
    { value: 'tahun', label: 'Tahun 2024/2025' },
    { value: 'bulan', label: 'Bulan ini' },
    { value: 'kustom', label: 'Kustom', icon: 'tune' },
];

export default function TimeframeFilter({ value, onChange, pilihan = PILIHAN }) {
    return <SegmentedControl label="Rentang waktu" options={pilihan} value={value} onChange={onChange} />;
}
