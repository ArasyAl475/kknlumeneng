import { cn } from '../../lib/cn.js';
import Icon from './Icon.jsx';

const NADA = {
    mint: 'bg-secondary-container text-on-secondary-container',
    netral: 'bg-surface-container text-on-surface-variant',
    tertiary: 'bg-tertiary-fixed text-on-tertiary-fixed',
    gelap: 'bg-tertiary-container/60 text-tertiary-fixed-dim',
    galat: 'bg-error-container text-on-error-container',
};

export default function Badge({ tone = 'netral', icon, pill = false, className, children }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 px-2 py-0.5 text-label-sm font-label-sm font-semibold',
                pill ? 'rounded-full' : 'rounded',
                NADA[tone],
                className,
            )}
        >
            {icon && <Icon name={icon} size={14} />}
            {children}
        </span>
    );
}
