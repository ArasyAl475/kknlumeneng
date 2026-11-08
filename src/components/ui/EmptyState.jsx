import { cn } from '../../lib/cn.js';
import Icon from './Icon.jsx';

/**
 * Keadaan kosong, datar (tanpa kotak) supaya aman dipakai di dalam card.
 * Ikon garis sederhana + satu judul + satu petunjuk, tanpa ilustrasi dekoratif.
 */
export default function EmptyState({ icon = 'bar_chart', title, description, action, className }) {
    return (
        <div className={cn('flex flex-col items-center justify-center px-6 py-10 text-center', className)}>
            <Icon name={icon} size={36} className="text-primary-container" />
            <p className="mt-3 text-headline-sm font-headline-sm text-on-surface">{title}</p>
            {description && <p className="mt-1 max-w-sm text-body-md font-body-md text-on-surface-variant">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
