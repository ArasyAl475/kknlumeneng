import { Badge, Card, EmptyState, Icon } from '../ui/index.js';
import SubstratePanel from './SubstratePanel.jsx';

/**
 * Card "Apakah cangkang kopi membantu jamur?": kesimpulan awam + dua sisi perbandingan + catatan pengamatan.
 * Satu card; sisi perbandingan hanya dipisahkan garis aksen kiri (tidak ada kotak di dalam card).
 * Props: judul, deskripsi, lencana?, kesimpulan (teks awam), cukup (bool),
 *        dengan { judul, lencana, baris }, tanpa { ... }, catatan?
 * Bila data belum cukup, tampil "Data belum cukup untuk dibandingkan", bukan tanda "-".
 */
export default function SubstrateComparison({ judul, deskripsi, lencana, kesimpulan, cukup = true, dengan, tanpa, catatan, className }) {
    return (
        <Card padding="lg" className={`space-y-5 ${className ?? ''}`}>
            <div>
                {lencana && (
                    <Badge tone="tertiary" icon="biotech" className="mb-2 py-1">
                        {lencana}
                    </Badge>
                )}
                <h2 className="text-headline-md font-headline-md text-on-surface">{judul}</h2>
                {deskripsi && <p className="mt-1 text-body-sm font-body-sm text-on-surface-variant">{deskripsi}</p>}
            </div>

            {cukup ? (
                <p className="text-headline-sm font-headline-sm text-primary">{kesimpulan}</p>
            ) : (
                <EmptyState icon="spa" title="Data belum cukup untuk dibandingkan" className="py-4" />
            )}

            <div className="space-y-6">
                <SubstratePanel variant="sorot" {...dengan} />
                <SubstratePanel variant="kontrol" {...tanpa} />
            </div>

            {catatan && (
                <p className="flex items-center gap-2 border-t border-outline-variant/30 pt-4 text-body-sm font-body-sm text-on-surface-variant">
                    <Icon name="update" size={18} className="text-secondary" />
                    <span>{catatan}</span>
                </p>
            )}
        </Card>
    );
}
