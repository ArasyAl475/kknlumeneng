# Komponen React + Tailwind: Hasil panen dan penjualan

Dibuat dari project Stitch **"UI/UX Redesign System"** (tema `AgroLumeneng Rural Intelligence`,
layar *Dashboard Panen Desa Lumeneng* versi Desktop dan Mobile).

> **Catatan stack.** Aplikasi Laravel ini sengaja memakai Blade + Tailwind **tanpa React** (lihat `CLAUDE.md`).
> Folder ini berdiri sendiri: punya `package.json`, Vite, dan Tailwind sendiri, dan **tidak** terhubung ke
> `vite.config.js`, `package.json`, atau Blade di akar proyek. Jangan dipasang ke aplikasi sebelum tim setuju.

## Menjalankan pratinjau

```
cd src
npm install
npm run dev        # http://localhost:5199   (tambah ?data=kosong untuk keadaan kosong)
npm run build      # cek build produksi
```

## Aturan tata letak: satu card, tanpa card di dalam card

- `Card` adalah satu-satunya wadah berkotak. Di dalamnya, isi dipisahkan dengan garis tipis (`border-t`, `divide-y`),
  jarak, atau garis aksen kiri (`border-l-4`), tidak dengan kotak ber-border atau berlatar lain.
- Contoh yang sudah diratakan: tiga angka ringkas grafik (dipisah garis tegak), sorotan puncak (garis aksen kiri),
  dua sisi perbandingan media tanam (garis aksen kiri + ikon + lencana teks), tabel (hanya garis antarbaris),
  dan `EmptyState` (datar).
- Ikon di wadah kecil (medalion) dan lencana/pil tetap ada karena itu bagian dari rancangan Stitch, bukan card.

## Isi

```
design/
  DESIGN.md           dokumen desain asli dari Stitch (warna, huruf, bentuk, komponen)
  tokens.js           semua token: warna, huruf, radius, jarak, bayangan, titik pecah
  tailwind.preset.js  preset Tailwind dari token (cukup `presets: [desain]`)
styles/index.css      lapisan dasar Tailwind + gaya ikon
lib/                  format angka Indonesia (kg, Rp, %), helper kelas
components/
  ui/                 Icon, Card, Badge, Button, SegmentedControl, SelectField, EmptyState, Pagination
  layout/             TopAppBar, Footer, BottomNav (opsional, tidak dipakai di spesifikasi terbaru), PageContainer
  harvest/            PageHeader, StatusPill, TimeframeFilter, HeroMetricCard, MetricCard, MetricGrid,
                      TrendCard (+ TrendChart, TrendSummary), SubstrateComparison (+ SubstratePanel),
                      HarvestTable (+ timbanganKolom.jsx: definisi kolom tabel timbangan)
data/contohHasil.js   data contoh (rekaan) berbentuk seperti variabel HasilController
pages/HasilPanenPage.jsx  contoh perakitan seluruh halaman
```

Setiap komponen tidak memuat data: semua teks dan angka masuk lewat props, jadi bisa dipakai ulang
(mis. untuk halaman Toko atau Edukasi). Format angka sama dengan `App\Services\Toko\FormatHasil`.

## Hasil ekstraksi desain

**Warna** (skema Material 3, nama kelas Tailwind sama dengan nama token Stitch):
primary `#00361f`, primary-container `#164e32`, secondary `#006c49`, secondary-container `#6cf8bb`,
tertiary `#003625`, surface/background `#f1fcf2`, surface-container-lowest `#ffffff`,
on-surface `#141e18`, outline-variant `#c0c9c0`, error `#ba1a1a`, dan seluruh varian lainnya di `design/tokens.js`.

**Huruf:** Plus Jakarta Sans. Skala: display-hero 40/800, display-hero-mobile 28/800, headline-lg 24/700,
headline-md 20/700, headline-sm 16/700, metric-number 30/700, body-lg 16, body-md 14, body-sm 12, label-md 13/600, label-sm 11/600.

**Layout:** HP 1 kolom (padding 1rem, target sentuh 48px, navigasi bawah); tablet 2 kolom; desktop maksimal
`max-w-7xl`, baris kartu angka 5 kolom (1 sorotan + 4), lalu grid 7:5 (grafik : perbandingan), lalu tabel rincian.

**Bentuk:** kartu `rounded-xl` + garis tipis + bayangan lembut; kartu sorotan hijau hutan; filter berbentuk pil.

## Yang perlu diketahui

- **Radius.** DESIGN.md menulis skala lebih besar (DEFAULT 0.5rem, lg 1rem) daripada konfigurasi Tailwind yang
  benar-benar dipakai layar Stitch (DEFAULT 0.25rem, lg 0.5rem, xl 0.75rem). Yang diikuti adalah konfigurasi layar.
- **Warna di uraian vs token.** Uraian DESIGN.md menyebut secondary `#10b981` dan kanvas `#f4f7f4`, sedangkan token
  yang dipakai layar `#006c49` dan `#f1fcf2`. Warna uraian disimpan sebagai `brand-*` (mis. `bg-brand-emerald`).
- **Data layar Stitch bukan data desa.** Nama petani, target musim, kapasitas kumbung, dan sejenisnya di layar Stitch
  rekaan; tidak ikut dibawa. Komponen hanya menampilkan apa yang dikirim lewat props.
- **Ikon.** Material Symbols dipasang lewat npm (bukan CDN), tetapi berkas fontnya penuh (~3,6 MB). Sebelum dipakai
  di produksi, kecilkan (subset ke ikon yang dipakai) atau ganti dengan SVG sebaris, karena sinyal warga sering lemah.
- **Perbedaan dengan halaman Blade yang sudah jadi.** Halaman `/toko/hasil` di Laravel memakai palet lumut/hitam
  pilihan Anda dan font yang sama; folder ini mengikuti rancangan Stitch apa adanya.
