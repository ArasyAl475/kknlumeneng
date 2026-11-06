import { cn } from '../../lib/cn.js';
import { Badge, Icon } from '../ui/index.js';

/**
 * Satu sisi perbandingan media tanam, tanpa kotak (aman di dalam card). Pembeda antarsisi:
 * garis aksen kiri tebal, ikon (centang / minus), dan lencana teks, jadi tetap jelas tanpa warna.
 * variant 'sorot'  : dengan cangkang kopi.
 * variant 'kontrol': tanpa cangkang kopi.
 * Props: judul, lencana, baris [{ label, nilai, tebal? }]
 */
export default function SubstratePanel({ variant = 'kontrol', judul, lencana, baris }) {
    const sorot = variant === 'sorot';

    return (
        <section aria-label={judul} className={cn('border-l-4 pl-4', sorot ? 'border-secondary' : 'border-outline-variant')}>
            <div className="mb-3 flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-2">
                <div className="flex items-center gap-2">
                    <Icon name={sorot ? 'check_circle' : 'remove_circle'} size={22} filled={sorot} className={sorot ? 'text-secondary' : 'text-outline'} />
                    <h3 className={cn('text-headline-sm font-headline-sm', sorot ? 'text-primary' : 'text-on-surface')}>{judul}</h3>
                </div>
                {lencana && <Badge tone={sorot ? 'mint' : 'netral'}>{lencana}</Badge>}
            </div>
            <dl className="text-body-sm font-body-sm">
                {baris.map((b) => (
                    <div key={b.label} className="flex items-center justify-between border-b border-outline-variant/20 py-2 last:border-b-0">
                        <dt className="text-on-surface-variant">{b.label}</dt>
                        <dd className={cn('text-headline-sm font-headline-sm', b.tebal && sorot ? 'text-primary' : 'text-on-surface')}>{b.nilai}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
