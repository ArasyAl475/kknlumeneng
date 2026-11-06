import { Badge, Card, Icon } from '../ui/index.js';

/**
 * Kartu sorotan (hijau hutan): angka utama desa, mis. uang dari jamur terjual.
 * Props: label, ikon, nilai (teks siap tampil), badge?, deskripsi?, tautan? { label, href }
 */
export default function HeroMetricCard({ label, ikon = 'payments', nilai, badge, deskripsi, tautan, className }) {
    return (
        <Card variant="hero" className={`relative flex flex-col justify-between overflow-hidden ${className ?? ''}`}>
            <div aria-hidden="true" className="pointer-events-none absolute -bottom-4 -right-4 h-28 w-28 rounded-full bg-surface-container-lowest/5 blur-xl" />
            <div>
                <div className="mb-3 flex items-center justify-between gap-2">
                    <span className="text-label-md font-label-md font-medium tracking-wide text-on-primary-container">{label}</span>
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container-lowest/10 text-secondary-fixed">
                        <Icon name={ikon} />
                    </div>
                </div>
                <div className="mb-1 whitespace-nowrap text-metric-number font-metric-number tracking-tight text-on-primary lg:text-metric-number-compact lg:font-metric-number-compact">{nilai}</div>
                {badge && <Badge tone="gelap">{badge}</Badge>}
            </div>
            {(deskripsi || tautan) && (
                <div className="mt-4 border-t border-on-primary-container/20 pt-3">
                    {deskripsi && <p className="mb-2 text-body-sm font-body-sm leading-tight text-on-primary-container/90">{deskripsi}</p>}
                    {tautan && (
                        <a href={tautan.href} className="inline-flex items-center gap-1 text-label-md font-label-md font-bold text-secondary-container hover:underline">
                            {tautan.label}
                            <Icon name="arrow_forward" size={16} />
                        </a>
                    )}
                </div>
            )}
        </Card>
    );
}
