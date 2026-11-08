import { borderRadius, boxShadow, brand, colors, fontFamily, fontSize } from './tokens.js';

// Preset Tailwind: cukup `presets: [desain]` di tailwind.config.js agar kelas seperti
// bg-primary-container, text-metric-number, rounded-xl, dan shadow-card tersedia.
// Jarak (space-md, gutter, margin, ...) dipakai lewat nilai biasa Tailwind (p-4, gap-6) agar tidak
// menimpa skala bawaan; nilainya tetap ada di tokens.js bila dibutuhkan.
export default {
    theme: {
        extend: {
            colors: { ...colors, brand },
            fontFamily,
            fontSize,
            borderRadius,
            boxShadow,
        },
    },
};
