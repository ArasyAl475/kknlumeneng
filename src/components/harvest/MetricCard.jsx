import { Card, Icon } from '../ui/index.js';

/**
 * Kartu angka biasa: ikon garis di kiri atas, angka besar, satuan kecil, keterangan.
 * Props: label, ikon, nilai (teks angka), satuan?, kosong? (bool), keterangan?, ikonKeterangan?,
 *        kaki? { kiri, kanan }  garis bawah opsional
 * Bila `kosong`, angka diredam dan diganti keterangan "belum ada catatan" agar halaman tidak penuh angka 0.
 */
export default function MetricCard({ label, ikon, nilai, satuan, kosong = false, keterangan, ikonKeterangan, kaki }) {
    return (
        <Card className="flex flex-col justify-between">
            <div>
                <div className="mb-2 flex items-center justify-between gap-2">
                    <span className="text-label-md font-label-md font-medium text-on-surface-variant">{label}</span>
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container-low text-secondary">
                        <Icon name={ikon} />
                    </div>
                </div>
                <div className={kosong ? 'mb-1 text-headline-md font-headline-md text-on-surface-variant' : 'mb-1 text-metric-number font-metric-number tracking-tight text-on-surface'}>
                    {nilai}
                    {satuan && <span className="ml-1 text-headline-sm font-headline-sm font-normal text-on-surface-variant">{satuan}</span>}
                </div>
                {keterangan && (
                    <p className="flex items-center gap-1 text-body-sm font-body-sm text-on-surface-variant">
                        {ikonKeterangan && <Icon name={ikonKeterangan} size={16} className="text-outline" />}
                        <span>{keterangan}</span>
                    </p>
                )}
            </div>
            {kaki && (
                <div className="mt-4 flex items-center justify-between border-t border-outline-variant/30 pt-3 text-body-sm font-body-sm text-on-surface-variant">
                    <span>{kaki.kiri}</span>
                    {kaki.kanan && <span className="font-semibold text-secondary">{kaki.kanan}</span>}
                </div>
            )}
        </Card>
    );
}
