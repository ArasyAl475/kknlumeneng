import { cn } from '../../lib/cn.js';

/**
 * SATU-SATUNYA wadah berkotak di halaman. Aturan: tidak ada card di dalam card.
 * Di dalam card, pisahkan isi dengan garis tipis (border-t / divide-y), jarak, atau garis aksen kiri,
 * jangan dengan kotak ber-border atau berlatar lain.
 */
const VARIAN = {
    // Permukaan standar: putih, garis tipis, bayangan lembut (Elevation layer 1).
    surface: 'bg-surface-container-lowest border border-outline-variant/50 shadow-card',
    // Kartu sorotan: hijau botol penuh dengan cahaya halus (Elevation layer 2).
    hero: 'bg-primary-container text-on-primary shadow-hero',
};

const PADDING = { md: 'p-5', lg: 'p-6' };

export default function Card({ as: Tag = 'div', variant = 'surface', padding = 'md', className, children, ...sisa }) {
    return (
        <Tag className={cn('rounded-xl', VARIAN[variant], PADDING[padding], className)} {...sisa}>
            {children}
        </Tag>
    );
}
