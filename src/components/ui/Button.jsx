import { cn } from '../../lib/cn.js';
import Icon from './Icon.jsx';

const VARIAN = {
    // Tombol utilitas (Filter, Export): bergaris tepi, ikon hijau hutan.
    outline: 'border border-outline-variant bg-surface-container-lowest text-on-surface hover:bg-surface-container',
    solid: 'bg-primary-container text-on-primary hover:bg-primary',
    ghost: 'text-on-surface-variant hover:bg-surface-container',
};

// Tinggi minimal 48px sesuai aturan target sentuh pada DESIGN.md (layar kecil).
export default function Button({ as: Tag = 'button', variant = 'outline', icon, className, children, ...sisa }) {
    const tipe = Tag === 'button' ? { type: 'button' } : {};

    return (
        <Tag
            className={cn(
                'inline-flex min-h-12 items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-label-md font-label-md font-medium transition-colors duration-150 disabled:opacity-50 sm:min-h-0',
                VARIAN[variant],
                className,
            )}
            {...tipe}
            {...sisa}
        >
            {icon && <Icon name={icon} size={18} className={variant === 'outline' ? 'text-primary' : ''} />}
            {children}
        </Tag>
    );
}
