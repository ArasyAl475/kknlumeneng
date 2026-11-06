---
name: AgroLumeneng Rural Intelligence
colors:
  surface: '#f1fcf2'
  surface-dim: '#d1ddd3'
  surface-bright: '#f1fcf2'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#ebf7ed'
  surface-container: '#e5f1e7'
  surface-container-high: '#e0ebe1'
  surface-container-highest: '#dae5dc'
  on-surface: '#141e18'
  on-surface-variant: '#404942'
  inverse-surface: '#28332c'
  inverse-on-surface: '#e8f4ea'
  outline: '#717972'
  outline-variant: '#c0c9c0'
  surface-tint: '#33694a'
  primary: '#00361f'
  on-primary: '#ffffff'
  primary-container: '#164e32'
  on-primary-container: '#86be9a'
  inverse-primary: '#9ad3ae'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#003625'
  on-tertiary: '#ffffff'
  tertiary-container: '#004f38'
  on-tertiary-container: '#66c49d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#b6f0c9'
  primary-fixed-dim: '#9ad3ae'
  on-primary-fixed: '#002111'
  on-primary-fixed-variant: '#195034'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#97f5cc'
  tertiary-fixed-dim: '#7bd8b1'
  on-tertiary-fixed: '#002115'
  on-tertiary-fixed-variant: '#00513a'
  background: '#f1fcf2'
  on-background: '#141e18'
  surface-variant: '#dae5dc'
typography:
  display-hero:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '800'
    lineHeight: 48px
    letterSpacing: -0.02em
  display-hero-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '800'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '700'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '700'
    lineHeight: 24px
  metric-number:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  metric-number-compact:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '700'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '600'
    lineHeight: 18px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-md: 1.25rem
  gutter-lg: 1.5rem
  margin: 1rem
  margin-md: 2rem
  margin-lg: 3rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system establishes a grounded, welcoming, and precise rural intelligence dashboard tailored for agrarian communities, village administrators, and agricultural co-ops. Balancing organic warmth with data clarity, the aesthetic reflects the fertile, montane character of coffee plantations and mushroom cultivation.

The design movement combines **Modern Organic Functionalism** with gentle tactile cues:
- Crisp white content containers resting against a breathable, pale sage canvas.
- Deep botanical greens conveying institutional trustworthiness, stability, and stewardship.
- Emerald and mint accents indicating yield vitality, positive differentials, and actionable insights.
- Restraint in ornamentation: illustrations and empty states leverage monoline botanical iconography and clear data tables rather than cluttering graphics.
- High legibility and spacious touch-targets designed for dual usage across dusty field tablets, handheld mobile devices, and village desktop kiosks.

## Colors

The palette is anchored by lush botanical tones balanced against soft organic neutrals:

- **Primary (`#164e32`)**: Deep Forest Green. Serves as the bedrock brand color for hero KPI highlights, high-impact container banners, master headers, and persistent app footers.
- **Secondary (`#10b981`)**: Emerald Accent. Highlights positive growth metrics, key trendlines, active filters, and interactive button states.
- **Tertiary (`#047857`)**: Rich Foliage Green. Used for interactive links, icons on neutral surfaces, subtle hover treatments, and badge borders.
- **Neutral (`#1a241e`)**: Deep Charcoal Foliage. High-contrast typography base ensuring effortless outdoor readability in direct daylight.

### Semantic & Surface Rules
- **Canvas Base**: Soft Sage Tint (`#f4f7f4`) creating gentle contrast without eye fatigue.
- **Card Surfaces**: Pure White (`#ffffff`) for elevated metric cards, data grids, and comparison matrices.
- **Subtle Borders**: Soft Leaf Gray (`#e2e8e3`) for divider rules and card perimeters.
- **Muted Text**: Forest Gray (`#526056`) for units of measurement (`kg`, `baglog`), timestamps, and descriptive sub-labels.
- **Subtle Badges / Tints**: Light Mint Cream (`#ecfdf5`) for comparison highlights and empty state containers.

## Typography

The design system exclusively standardizes on **Plus Jakarta Sans**, bridging geometric modernity with welcoming humanist touches. The rounded contours and generous counters prevent eye strain during rapid inventory review.

### Usage Standards
- **Display & Main Headings (`display-hero`, `headline-lg`)**: Bold, confident titles for village production sections and dashboard summaries. Tight tracking creates a professional editorial balance.
- **Numerical Metrics (`metric-number`)**: Distinctive weight for immediate harvest tally extraction (`kg`, currency totals). Units (`kg`, `baglog`) follow at a standardized `14px` regular weight with half-space kerning.
- **Data Tables & Comparison Grids**: Rows use `label-md` and `body-md` aligned across column baselines to facilitate rapid comparisons between substrate categories (coffee husk vs. control).

## Layout & Spacing

A responsive multi-column layout system structured for cross-device fluidity:
- **Mobile (< 640px)**: 1-column fluid stacking, `1rem` outer canvas padding, minimum `48px` interactive target height.
- **Tablet (640px - 1024px)**: 2-to-4 column responsive grid, `2rem` outer padding, `1.25rem` gutters.
- **Desktop (> 1024px)**: Max content boundary of `1200px` centered with `3rem` margins. The primary summary array divides into a dual layout: primary feature hero card (left) paired with secondary comparative notice (right), flowing into a 4-column KPI strip below.

Stacking logic prioritizes context: high-level financial turnover leads, followed by harvest breakdown cards, analytical substrate comparison, and secondary harvest records.

## Elevation & Depth

Visual hierarchy leverages crisp surface tiering and subtle botanical ambient shadows over exaggerated elevations:

1. **Canvas Level (Ground)**: Pale Sage Tint (`#f4f7f4`). Flat, unshadowed.
2. **Standard Surface (Layer 1)**: White (`#ffffff`) with a micro-border `1px solid #e2e8e3` and a soft ambient shadow: `0px 2px 8px -2px rgba(22, 78, 50, 0.04), 0px 1px 3px 0px rgba(0, 0, 0, 0.02)`.
3. **Hero Metric Accent (Layer 2)**: Deep Forest Green (`#164e32`) solid fill with an internal ambient glow: `0px 10px 25px -5px rgba(22, 78, 50, 0.25)`. Text, icons, and micro-labels on this surface invert to white and soft emerald (`#a7f3d0`).
4. **Modal / Popover Layer (Layer 3)**: Pure white with `0px 16px 32px -8px rgba(22, 78, 50, 0.12)` and backdrop barrier filter `blur(4px)` with `rgba(22, 78, 50, 0.2)`.

## Shapes

The interface embraces a **Rounded (`level 2`)** geometry, reflecting biological naturalness while maintaining structured dashboard discipline.

- **Base Radius (`0.5rem` / 8px)**: Inputs, filter pills, secondary buttons, and nested sub-cards.
- **Large Radius (`1rem` / 16px)**: Primary metric cards, empty-state containers, comparison summary panels, and navigation bars.
- **Full Radius (Pill / 9999px)**: Timeframe filter selectors, active status indicators, and micro notification badges.
- **Divider Rules**: 1px horizontal lines with soft radius terminations to delimit statistical sub-rows cleanly.

## Components

### Buttons & Filter Pills
- **Timeframe Filter Tabs**: Encapsulated segmented control on white background. Inactive items use transparent backgrounds with `#526056` text; active items highlight with `#164e32` background, white text, and `0.5rem` radius.
- **Utility Buttons (Filter, Export)**: Outlined border (`1px solid #d1dbd2`), soft white background, icon prepended with `#164e32`, hover state transitions to `#f4f7f4`.

### Cards & Metric Strips
- **Primary Hero Metric (Forest Tint)**: Used for master revenue/yield. Background `#164e32`, text `#ffffff`, iconography enclosed in a soft green translucent medallion (`rgba(255, 255, 255, 0.1)`).
- **Secondary Metric Cards**: White surface, top-left aligned outline icon (coffee cup, tea leaf, mushroom cap), large bold metric (`0,00 kg`), and a faint explanatory subtext below (`Belum ada catatan panen`).

### Substrate Comparison Matrix (Kopi vs. Non-Kopi)
- Structured dual-column grid comparing `Dengan cangkang kopi` and `Tanpa cangkang kopi`.
- Left-aligned parameter titles (`Jumlah baglog`, `Total panen jamur`, `Hasil per baglog`) with bold right-aligned numerical output.
- Intermittent zebra or hairline dividers (`#f0f4f1`) separating rows for effortless side-by-side scanning.

### Empty States & Visual Indicators
- **Minimalist Botanical Glyphs**: Instead of decorative illustrations, empty states use refined, center-aligned iconography (e.g., stylized bar-chart glyph or mushroom icon) rendered in `#164e32` or `#10b981`.
- **Informative Guidance**: Accompanied by a concise headline (`Belum ada data untuk periode ini`) and a single, calming instruction (`Grafik muncul setelah admin desa mencatat panen atau penjualan.`).

### Header & Announcement Strip
- **App Top Bar**: Deep forest green (`#164e32`) background with white typography, showing the village identity, sub-district hierarchy, and an alert link with an directional arrow (`Peringatan Dini →`).
- **Footer Disclaimer**: Deep forest tone with clear divider separating credits from early-warning civil alerts (BPBD advisory notes).