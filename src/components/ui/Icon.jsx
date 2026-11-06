import { cn } from '../../lib/cn.js';

/**
 * Ikon Material Symbols Outlined (set ikon yang dipakai rancangan Stitch).
 * Dekoratif: maknanya harus selalu ada pada teks di sebelahnya, jadi disembunyikan dari pembaca layar.
 */
export default function Icon({ name, size = 20, filled = false, className }) {
    return (
        <span
            aria-hidden="true"
            className={cn('material-symbols-outlined shrink-0', className)}
            style={{ fontSize: size, ...(filled ? { fontVariationSettings: "'FILL' 1, 'wght' 400" } : {}) }}
        >
            {name}
        </span>
    );
}
