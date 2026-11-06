import { Card, EmptyState, Icon, SegmentedControl } from '../ui/index.js';
import TrendChart from './TrendChart.jsx';
import TrendSummary from './TrendSummary.jsx';

/**
 * Card "Perkembangan Hasil Desa". Satu card, isinya dipisah garis tipis (tidak ada kotak di dalamnya):
 * judul + pilihan skala, sorotan puncak (garis aksen kiri), grafik, lalu tiga angka ringkas.
 * Props: judul, deskripsi, skala { pilihan, value, onChange }, banner? (node), grafik { labels, seri, sorot, format },
 *        ringkasan [{ label, nilai, ... }], kosong? { judul, deskripsi, aksi? }
 * Bila `kosong` diisi, grafik datar penuh nol tidak digambar dan keadaan kosong tampil sebagai gantinya.
 */
export default function TrendCard({ judul, deskripsi, skala, banner, grafik, ringkasan, kosong, className }) {
    return (
        <Card padding="lg" className={`space-y-6 ${className ?? ''}`}>
            <div className="flex flex-col justify-between gap-4 border-b border-outline-variant/30 pb-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="text-headline-md font-headline-md text-on-surface">{judul}</h2>
                    {deskripsi && <p className="text-body-sm font-body-sm text-on-surface-variant">{deskripsi}</p>}
                </div>
                {skala && <SegmentedControl variant="subtle" label="Skala waktu grafik" options={skala.pilihan} value={skala.value} onChange={skala.onChange} />}
            </div>

            {kosong ? (
                <EmptyState icon="bar_chart" title={kosong.judul} description={kosong.deskripsi} action={kosong.aksi} />
            ) : (
                <>
                    {banner && (
                        <div className="flex items-center gap-3 border-l-4 border-secondary py-1 pl-4">
                            <Icon name="trending_up" size={20} className="text-secondary" />
                            <div className="text-body-md font-body-md text-on-surface">{banner}</div>
                        </div>
                    )}
                    <TrendChart {...grafik} />
                    {ringkasan && <TrendSummary item={ringkasan} />}
                </>
            )}
        </Card>
    );
}
