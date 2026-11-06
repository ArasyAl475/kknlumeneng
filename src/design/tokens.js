/**
 * Token desain "AgroLumeneng Rural Intelligence".
 * Sumber: project Stitch "UI/UX Redesign System" (designTheme + tailwind-config pada layar dasbor).
 * Nilai di sini sama dengan yang dipakai layar Stitch, jadi hasil render sama dengan rancangan.
 */

// Warna bernama (skema Material 3). Dipakai sebagai kelas Tailwind: bg-primary, text-on-surface, dst.
export const colors = {
    background: '#f1fcf2',
    surface: '#f1fcf2',
    'surface-bright': '#f1fcf2',
    'surface-dim': '#d1ddd3',
    'surface-variant': '#dae5dc',
    'surface-tint': '#33694a',
    'surface-container-lowest': '#ffffff',
    'surface-container-low': '#ebf7ed',
    'surface-container': '#e5f1e7',
    'surface-container-high': '#e0ebe1',
    'surface-container-highest': '#dae5dc',

    'on-background': '#141e18',
    'on-surface': '#141e18',
    'on-surface-variant': '#404942',
    'inverse-surface': '#28332c',
    'inverse-on-surface': '#e8f4ea',
    outline: '#717972',
    'outline-variant': '#c0c9c0',

    primary: '#00361f',
    'on-primary': '#ffffff',
    'primary-container': '#164e32',
    'on-primary-container': '#86be9a',
    'inverse-primary': '#9ad3ae',
    'primary-fixed': '#b6f0c9',
    'primary-fixed-dim': '#9ad3ae',
    'on-primary-fixed': '#002111',
    'on-primary-fixed-variant': '#195034',

    secondary: '#006c49',
    'on-secondary': '#ffffff',
    'secondary-container': '#6cf8bb',
    'on-secondary-container': '#00714d',
    'secondary-fixed': '#6ffbbe',
    'secondary-fixed-dim': '#4edea3',
    'on-secondary-fixed': '#002113',
    'on-secondary-fixed-variant': '#005236',

    tertiary: '#003625',
    'on-tertiary': '#ffffff',
    'tertiary-container': '#004f38',
    'on-tertiary-container': '#66c49d',
    'tertiary-fixed': '#97f5cc',
    'tertiary-fixed-dim': '#7bd8b1',
    'on-tertiary-fixed': '#002115',
    'on-tertiary-fixed-variant': '#00513a',

    error: '#ba1a1a',
    'on-error': '#ffffff',
    'error-container': '#ffdad6',
    'on-error-container': '#93000a',
};

// Warna merek dari uraian DESIGN.md (bagian "Colors"). Nilai ini TIDAK sama dengan token di atas
// (mis. secondary DESIGN.md = #10b981, token = #006c49); dipakai bila ingin mengikuti uraian tertulisnya.
export const brand = {
    forest: '#164e32', // Primary: hero KPI, header, footer
    emerald: '#10b981', // Secondary: tren positif, filter aktif
    foliage: '#047857', // Tertiary: tautan, ikon di permukaan netral
    charcoal: '#1a241e', // Netral: teks utama
    sage: '#f4f7f4', // Kanvas
    leaf: '#e2e8e3', // Garis pembatas
    'forest-gray': '#526056', // Teks redup (satuan, keterangan)
    mint: '#ecfdf5', // Latar sorotan/keadaan kosong
};

export const fontFamily = {
    sans: ['"Plus Jakarta Sans Variable"', '"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
};

// [ukuran, { lineHeight, letterSpacing, fontWeight }] sesuai tailwind-config Stitch.
export const fontSize = {
    'display-hero': ['40px', { lineHeight: '48px', letterSpacing: '-0.02em', fontWeight: '800' }],
    'display-hero-mobile': ['28px', { lineHeight: '34px', letterSpacing: '-0.01em', fontWeight: '800' }],
    'headline-lg': ['24px', { lineHeight: '32px', letterSpacing: '-0.015em', fontWeight: '700' }],
    'headline-md': ['20px', { lineHeight: '28px', letterSpacing: '-0.01em', fontWeight: '700' }],
    'headline-sm': ['16px', { lineHeight: '24px', fontWeight: '700' }],
    'metric-number': ['30px', { lineHeight: '36px', letterSpacing: '-0.02em', fontWeight: '700' }],
    'metric-number-compact': ['22px', { lineHeight: '28px', letterSpacing: '-0.01em', fontWeight: '700' }],
    'body-lg': ['16px', { lineHeight: '24px', fontWeight: '400' }],
    'body-md': ['14px', { lineHeight: '20px', fontWeight: '400' }],
    'body-sm': ['12px', { lineHeight: '18px', fontWeight: '400' }],
    'label-md': ['13px', { lineHeight: '18px', letterSpacing: '0.01em', fontWeight: '600' }],
    'label-sm': ['11px', { lineHeight: '16px', letterSpacing: '0.02em', fontWeight: '600' }],
};

// Radius yang benar-benar dipakai layar Stitch (tailwind-config). Catatan: DESIGN.md menulis skala
// yang lebih besar (DEFAULT 0.5rem, lg 1rem); layar memakai skala di bawah ini, jadi inilah yang diikuti.
export const borderRadius = {
    DEFAULT: '0.25rem',
    lg: '0.5rem',
    xl: '0.75rem',
    full: '9999px',
};

export const spacing = {
    xs: '0.25rem',
    sm: '0.5rem',
    md: '1rem',
    lg: '1.5rem',
    xl: '2rem',
    gutter: '1rem',
    'gutter-md': '1.25rem',
    'gutter-lg': '1.5rem',
    margin: '1rem',
    'margin-md': '2rem',
    'margin-lg': '3rem',
};

// Bayangan dari bagian "Elevation & Depth".
export const boxShadow = {
    card: '0px 2px 8px -2px rgba(22, 78, 50, 0.04), 0px 1px 3px 0px rgba(0, 0, 0, 0.02)',
    hero: '0px 10px 25px -5px rgba(22, 78, 50, 0.25)',
    popover: '0px 16px 32px -8px rgba(22, 78, 50, 0.12)',
};

// Titik pecah layout dari bagian "Layout & Spacing".
export const layout = {
    mobileMax: 640,
    desktopMin: 1024,
    contentMax: '1200px',
    touchTarget: '48px',
};
