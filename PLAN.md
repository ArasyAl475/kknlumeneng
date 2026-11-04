# PLAN.md — Rencana Kerja Web Terpadu Desa Lumeneng

Dokumen ini dikerjakan Claude Code **per fase**. Aturan umum ada di `CLAUDE.md`.
Perintah memulai: ketik `/fase 0` (atau: "Baca CLAUDE.md dan PLAN.md, lalu kerjakan Fase 0").
Setiap tugas yang selesai: centang `[x]`, jalankan `/cek`, commit di branch fase tersebut.
Satu fase = satu branch = satu PR. Jangan loncat fase.

Hasil akhir: **landing page**, **menu EWS** (dashboard publik), **menu Admin**,
serta tempat untuk modul **Toko** dan **Edukasi** yang dikerjakan rekan lewat branch.

---

## Fase 0 — Fondasi repo (branch `fondasi/awal`)
Tujuan: kerangka yang membuat 4 orang bisa bekerja paralel tanpa saling bentrok.

- [x] Baca SELURUH: `CLAUDE.md`, `docs/ews/*`, `referensi/prototipe-ews/*`.
- [x] Cek `php -v`, `composer -V`, `node -v`. Jika ada yang hilang: berhenti, jelaskan cara
      memasangnya langkah demi langkah, tunggu pengguna.
- [x] Buat proyek Laravel stable terbaru di folder sementara, lalu pindahkan ke root TANPA
      menimpa: `CLAUDE.md`, `PLAN.md`, `README.md`, `CONTRIBUTING.md`, `pint.json`,
      `.claude/`, `.github/`, `docs/`, `referensi/`, `firmware/`.
- [x] Jika scaffold membuat `AGENTS.md`/`CLAUDE.md` bawaan Laravel: JANGAN timpa `CLAUDE.md`
      kita. Hapus berkas bawaan itu. Jangan jalankan `laravel/boost` / `boost:install`
      kecuali diminta pengguna (Boost menulis ulang CLAUDE.md).
- [x] Gabungkan `.gitignore` dengan menambah: `.claude/settings.local.json`, `CLAUDE.local.md`,
      `/referensi/**/.env`, `*.sqlite`, `/storage/*.key`.
- [x] Sesuaikan `php-version` di `.github/workflows/ci.yml` dengan `require.php` di composer.json.
- [x] `.env.example` bersih (tanpa rahasia) berisi: `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`,
      `DB_CONNECTION=sqlite`, `MODUL_EWS=true`, `MODUL_TOKO=false`, `MODUL_EDUKASI=false`,
      `EWS_API_KEY=`, `TELEGRAM_BOT_TOKEN=`, `TELEGRAM_CHAT_ID=`, `TELEGRAM_COOLDOWN_MENIT=30`,
      `EWS_ALAT_OFFLINE_MENIT=15`, `EWS_VALIDASI_JEDA_MENIT=30`, `ADMIN_EMAIL=`, `ADMIN_PASSWORD=`.
- [x] Tailwind + Vite aktif. Pasang `leaflet`, `leaflet-draw`, `chart.js` lewat npm. Tidak ada CDN.
- [x] Tema: font mudah dibaca (mis. Atkinson Hyperlegible, di-host sendiri lewat npm
      `@fontsource/...`), token warna di `resources/css/app.css` (`@theme`), termasuk warna
      status: hijau/kuning/merah dengan kontras cukup.
- [x] Komponen Blade bersama di `resources/views/components/`: `tombol`, `kartu`, `lencana`
      (untuk status), `pesan` (flash sukses/galat), `judul-halaman`.
- [x] Layout publik `layouts/app.blade.php` (header, menu dari `config/modul.php`, footer
      dengan disclaimer) dan layout admin `layouts/admin.blade.php` (sidebar memakai
      `@includeIf('admin.menu.<modul>')` untuk tiap modul).
- [x] Rute: `routes/web.php` & `routes/admin.php` memuat otomatis `routes/web/*.php` dan
      `routes/admin/*.php` (dan `routes/api/*.php` untuk API). Buat berkas kosong berkomentar
      untuk `landing`, `ews`, `toko`, `edukasi`.
- [x] Autentikasi admin: kolom `users.is_admin`, middleware alias `admin`, halaman `/masuk`,
      keluar, throttle login. `AdminSeeder` membuat akun dari `.env`, menolak kata sandi lemah
      di luar lokal. Rute `/admin` selalu terlindungi.
- [x] Halaman `/admin` (beranda admin) + halaman galat 403/404/500 berbahasa Indonesia.
- [x] `DatabaseSeeder` memanggil seeder per modul.
- [x] Tes: login admin berhasil/gagal, non-admin ditolak, rute admin butuh login.
- [x] `/cek` hijau. Commit. Beri tahu pengguna cara membuat tag `v0.1-fondasi` setelah merge.

**Selesai jika:** `composer install && php artisan migrate --seed && npm run build` berjalan
bersih di mesin baru; CI hijau; rekan bisa membuat branch dan menambah modul hanya dengan
menambah berkas di folder modulnya.

---

## Fase 1 — Landing page (branch `landing/awal`)
- [ ] Hero: nama program, satu kalimat tujuan, tombol utama "Lihat Status Peringatan Dini".
- [ ] Ringkasan status EWS langsung (Aman/Waspada/Awas terburuk) memakai layanan EWS yang sama
      dengan dashboard; jika modul/data belum ada, tampilkan keadaan "belum ada data" yang rapi.
- [ ] Kartu modul: EWS, Toko, Edukasi (mengikuti `config/modul.php`; yang belum siap
      tampil "segera hadir" tanpa tautan).
- [ ] Bagian "Tentang program", "Mitra" (Karang Taruna Giri, Petani Muda Jaya), "Kontak/lokasi",
      nomor darurat (BPBD Kab. Pekalongan: ISI SETELAH DIVERIFIKASI, jangan mengarang nomor).
- [ ] Responsif, cepat (tanpa gambar besar tak terkompresi), judul/deskripsi meta, favicon.
- [ ] Teks konten disimpan di satu tempat mudah diedit (berkas lang/config), bukan tersebar.

**Selesai jika:** halaman terbaca baik di HP 360px, skor Lighthouse mobile baik (Performance,
Accessibility ≥ 90 sebagai target), tidak ada skrip/CSS dari CDN.

---

## Fase 2 — EWS inti: data, aturan, API alat (branch `ews/inti`)
Acuan: `docs/ews/ATURAN_EWS.md`, `docs/ews/API_ALAT.md`, prototipe (hanya gambaran).

Skema (fresh, tidak perlu kompatibel dengan basis data prototipe):
- `ews_wilayah`: id, nama (unik), poligon (json `[[lat,lng],...]`), status_awas (bool),
  awas_sumber, awas_sejak, timestamps, **soft delete** (riwayat tetap utuh).
- `ews_pembacaan`: id, `wilayah_id` (FK, bukan teks nama), `kelembapan_tanah`, `curah_hujan`,
  `kemiringan`, `kelembapan_udara` (semua nullable), `kabel_putus`, `status`, `warna`,
  `pemicu`, `validasi` (null/menunggu/disetujui/ditolak), `divalidasi_pada`,
  `divalidasi_oleh` (user), `created_at` + index `(wilayah_id, created_at)`.
- `ews_pengaturan`: kunci (PK), nilai. Isi awal dari `config/ews.php`.

Tugas:
- [ ] Migrasi, model (`App\Models\Ews\*`), factory, `EwsSeeder` (3 wilayah contoh, ambang awal).
- [ ] `App\Services\Ews\PenilaianStatus`: satu-satunya tempat aturan status (lihat aturan).
- [ ] **Tes unit PenilaianStatus lengkap** (semua kombinasi level, nilai null, batas tepat,
      kabel putus, kemiringan negatif).
- [ ] `POST /api/sensor` dan `GET /api/ping` sesuai `API_ALAT.md` (kompatibel dengan alat).
      FormRequest terpisah untuk API; middleware cek `X-API-KEY` (`hash_equals`); throttle.
      Terima nama lama `kelembapan` sebagai `kelembapan_tanah`.
- [ ] Logika permintaan validasi: satu laporan "menunggu" per wilayah, jeda setelah keputusan
      admin (`EWS_VALIDASI_JEDA_MENIT`), tidak membanjiri admin.
- [ ] `App\Services\Ews\PemberitahuTelegram`: kirim lewat antrean/`dispatchAfterResponse`,
      timeout pendek, anti-spam (`TELEGRAM_COOLDOWN_MENIT`), gagal kirim tidak menggagalkan API.
- [ ] Deteksi alat offline (tanpa kiriman ≥ `EWS_ALAT_OFFLINE_MENIT`).
- [ ] Perintah `php artisan ews:simulasi` (data acak realistis untuk demo/dev; menolak jalan
      di produksi).
- [ ] Perintah `ews:bersihkan` (hapus/ringkas pembacaan lama, mis. > 90 hari; dapat dijadwalkan).
- [ ] Tes fitur API: 201 sukses, 401 kunci salah/kosong, 422 data tidak valid, sensor null
      tersimpan, `kabel_putus` → Awas otomatis, laporan validasi tidak berulang.

**Selesai jika:** `curl` contoh di `API_ALAT.md` berhasil, semua tes hijau, dan firmware contoh
`firmware/esp32_contoh` bisa mengirim tanpa perubahan kontrak.

---

## Fase 3 — Dashboard EWS publik (branch `ews/dashboard`)
- [ ] Satu endpoint JSON `GET /ews/data` (nama bebas, harus konsisten) yang di-cache singkat
      (5–10 detik) dan dibatalkan saat data baru masuk. Tanpa N+1.
- [ ] Panel status utama (kondisi terburuk dari semua wilayah): warna + ikon + kata +
      saran tindakan sederhana per status + waktu data terakhir.
- [ ] Kartu 4 sensor per wilayah dengan level (normal/waspada/lewat batas) dan satuan jelas;
      sensor kosong tampil "tidak ada data", bukan 0.
- [ ] Peta Leaflet: poligon wilayah berwarna sesuai status, klik wilayah → detailnya.
- [ ] Grafik Chart.js: pilih sensor dan rentang (1 jam / 24 jam / 7 hari), garis ambang batas.
- [ ] Log kejadian terbaru. Banner "alat tidak mengirim data" saat offline.
- [ ] Pembaruan otomatis berkala, hemat data, tetap jalan saat sebentar kehilangan sinyal
      (tampilkan "terakhir diperbarui" + status koneksi).
- [ ] Disclaimer "bukan peringatan resmi BPBD" terlihat jelas.
- [ ] Aksesibilitas: status bukan hanya warna, `aria-live` untuk perubahan status, fokus terlihat.
- [ ] Tes fitur untuk endpoint data; tes tampilan dasar (halaman 200, teks kunci muncul).

**Selesai jika:** terbaca nyaman di HP, aman/kuning/merah dapat dibedakan tanpa melihat warna,
dan 50 pengunjung bersamaan tidak membuat server kewalahan (berkat cache).

---

## Fase 4 — Admin EWS (branch `ews/admin`)
- [ ] Menu `resources/views/admin/menu/ews.blade.php` + halaman di `admin/ews/`.
- [ ] Antrean validasi: lihat nilai semua sensor, setujui (→ Awas + Telegram) atau tolak.
- [ ] Cabut status Awas (dengan konfirmasi dan catatan alasan).
- [ ] Ubah ambang batas 3 sensor (validasi: batas validasi > batas waspada; rentang wajar).
- [ ] Kelola wilayah: gambar poligon di peta (minimal 3 titik, tidak menyilang, bukan garis),
      ubah nama, hapus (soft delete), tampilkan "ID alat" untuk dipasang di ESP32.
- [ ] Riwayat pembacaan dengan filter wilayah/tanggal + ekspor CSV.
- [ ] Log aktivitas admin sederhana (siapa menyetujui/mencabut/mengubah ambang, kapan).
- [ ] Halaman "Status alat": terakhir mengirim kapan, offline/online per wilayah.
- [ ] Simulator (hanya admin + non-produksi) untuk mencoba tanpa alat.
- [ ] Tes: hanya admin yang bisa; tiap aksi mengubah status sesuai aturan; validasi ambang.

**Selesai jika:** seluruh alur "data masuk → perlu validasi → admin setujui → Awas →
Telegram → admin cabut" bisa dicoba dari simulator dan lolos tes.

---

## Fase 5 — Penutup EWS & siap tayang (branch `ews/rilis`)
- [ ] Uji dengan alat ESP32 sungguhan bersama pembuat alat; catat hasil di `docs/ews/UJI_ALAT.md`.
- [ ] Kalibrasi ambang kemiringan bersama tim (nilai 5°/15° di prototipe hanya sementara).
- [ ] Daftar periksa keamanan: `APP_DEBUG=false`, HTTPS, token baru, kata sandi admin kuat,
      throttle aktif, tidak ada rute simulator di produksi, cadangan basis data terjadwal.
- [ ] `docs/DEPLOY.md`: langkah deploy sesuai hosting yang dipilih (lihat keputusan terbuka),
      termasuk cron `schedule:run` bila hosting mendukung.
- [ ] Halaman/PDF panduan singkat untuk kader Karang Taruna (SOP: apa yang dilakukan saat
      Waspada/Awas, cara memvalidasi) — taut ke modul Edukasi bila sudah ada.
- [ ] Hapus `referensi/` jika sudah tidak dibutuhkan (opsional, lewat PR).

---

## Jalur paralel untuk rekan (mulai setelah tag `v0.1-fondasi`)
Rekan Toko dan Edukasi TIDAK menunggu EWS. Cara memulai:
1. `git pull` lalu `git checkout -b toko/awal` (atau `edukasi/awal`).
2. Buka Claude Code, ketik `/fitur-baru toko <apa yang ingin dibuat>`.
3. Kerja hanya di folder modul sendiri (struktur di `CLAUDE.md` bagian 5).
4. Nyalakan menu: ubah `MODUL_TOKO=true` di `.env` lokal; saat siap rilis, ubah default di
   `config/modul.php` lewat PR fondasi kecil.
5. `/siap-pr`, push, buka Pull Request.
Aturan: tabel berawalan `toko_`/`edukasi_`, menu admin di `admin/menu/<modul>.blade.php`,
pelanggan Toko memakai tabel `users` yang sama (tambah peran lewat migrasi baru milik Toko,
jangan mengubah migrasi fondasi).

---

## Catatan dari prototipe (perbaikan wajib di versi baru)
Prototipe sudah memuat banyak ide baik (aturan status, validasi admin, Awas ditahan, Telegram,
poligon wilayah). Yang HARUS diperbaiki, bukan disalin:
1. Tailwind CDN, Leaflet dari unpkg, Chart.js dari CDN → semua lewat npm/Vite.
2. Login admin bisa dimatikan lewat `.env` (default mati) dan simulator terbuka → login selalu
   wajib; simulator hanya admin + non-produksi.
3. Dashboard menghitung ulang payload untuk SETIAP pengunjung tiap 5 detik, dengan query berulang
   per wilayah (N+1) → cache singkat + query efisien.
4. Telegram dikirim di dalam permintaan API alat (timeout 10 detik) → lewat antrean/setelah respons.
5. Data sensor menunjuk wilayah lewat teks nama → pakai `wilayah_id`; wilayah dihapus lunak.
6. Kolom lama bernama `kelembapan` (sebenarnya tanah) → `kelembapan_tanah` (API tetap menerima
   nama lama).
7. Satu method `storeData` melayani simulator web dan API alat → pisahkan (FormRequest, controller).
8. Tidak ada pembersihan data lama → perintah `ews:bersihkan`.
9. Tidak ada peringatan saat alat berhenti mengirim selain banner → pertimbangkan notifikasi
   Telegram sekali saat offline (butuh scheduler/cron; lihat keputusan terbuka).
10. Berkas `.env` prototipe berisi token Telegram, kunci API, dan kata sandi admin lemah →
    token LAMA wajib dianggap bocor dan diganti; kata sandi admin kuat.

## Keputusan terbuka (tanyakan ke tim, jangan ditebak)
- [ ] "Udara" = **kelembapan udara** (seperti prototipe, hanya ditampilkan) atau **kualitas
      udara** (mis. PM2.5/CO2)? Ini mengubah kolom, satuan, dan ambang.
- [ ] Hosting produksi (hosting bersama cPanel atau VPS)? Menentukan antrean, cron, MySQL.
- [ ] Ambang kemiringan final setelah kalibrasi alat; satuan "derajat dari posisi pasang".
- [ ] Nomor darurat resmi (BPBD/desa) yang boleh ditampilkan.
- [ ] Toko: cakupan pembayaran (pesan lewat WhatsApp/COD dulu atau payment gateway).
- [ ] Siapa pemilik tiap modul (isi tabel di `CLAUDE.md`) dan siapa pemelihara repo.
