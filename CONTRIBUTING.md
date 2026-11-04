# Alur Kerja Tim (4 orang) — langkah demi langkah

Prinsip: **main selalu bisa jalan**. Tidak ada yang mengubah `main` langsung.
Semua perubahan lewat branch → Pull Request (PR) → ditinjau satu orang → digabung.

## A. Sekali saja (setiap anggota)
1. Pasang Git, PHP, Composer, Node.js.
2. Ambil proyek: `git clone <URL-repo-GitHub>` lalu `cd <nama-folder>`.
3. `composer install`
4. Salin pengaturan: `cp .env.example .env` (Windows PowerShell: `copy .env.example .env`)
5. `php artisan key:generate`
6. Isi `ADMIN_EMAIL`, `ADMIN_PASSWORD` (kata sandi kuat) dan `EWS_API_KEY` di `.env` milikmu
   sendiri. JANGAN kirim isi `.env` ke siapa pun.
7. `php artisan migrate --seed`
8. `npm install`
9. Jalankan: `npm run dev` (terminal 1) dan `php artisan serve` (terminal 2), buka
   http://localhost:8000

## B. Setiap mulai kerja
1. `git checkout main`
2. `git pull`
3. `git checkout -b <modul>/<topik-singkat>` (contoh: `toko/keranjang`)
4. Jika ada migrasi baru dari rekan: `php artisan migrate`

## C. Saat bekerja
- Boleh dibantu Claude Code: ketik `/fitur-baru <modul> <yang ingin dibuat>`.
- Ubah hanya berkas di folder modulmu (lihat `CLAUDE.md` bagian 5).
- Berkas bersama (lihat `CLAUDE.md` bagian 6) hanya diubah lewat branch `fondasi/...` terpisah.
- Menambah paket (composer/npm)? Lakukan di PR sendiri dan kabari grup.
- Jangan mengedit migrasi yang sudah ada di `main`. Buat migrasi baru.

## D. Sebelum membuka PR
1. `git fetch origin` lalu `git merge origin/main` (bawa perubahan terbaru; selesaikan konflik
   bila ada, atau minta bantuan Claude Code: "bantu selesaikan konflik merge ini").
2. Ketik `/siap-pr` di Claude Code (menjalankan format, tes, build, memeriksa batas modul).
3. `git add .` lalu `git commit -m "<modul>: <apa yang berubah>"`
4. `git push -u origin <nama-branch-kamu>`
5. Buka GitHub → "Compare & pull request" → isi template → pilih satu peninjau.

## E. Meninjau PR rekan
- Ada tanda centang hijau (CI) dan tidak ada berkas di luar modulnya (kecuali PR fondasi).
- Tidak ada rahasia/`.env`/token di perubahan. Admin selalu terlindungi login.
- Tampilannya dicoba di HP/lebar sempit. Pilih "Squash and merge".

## F. Untuk pemilik repo (sekali saja di GitHub)
1. Settings → Collaborators: tambahkan 3 rekan.
2. Settings → Branches → tambah aturan untuk `main`: wajib Pull Request, wajib 1 persetujuan,
   wajib status CI lulus, larang push paksa.
3. Settings → General → Pull Requests: aktifkan "Squash merging", hapus branch otomatis setelah merge.
4. Buat tag setelah Fase 0 digabung: `git tag v0.1-fondasi` lalu `git push origin v0.1-fondasi`.

## G. Aturan emas
- Jangan pernah commit `.env`, token Telegram, atau kunci API. Bila terlanjur: bilang ke grup,
  ganti token/kunci SAAT ITU JUGA (menghapus dari riwayat Git tidak cukup).
- Satu PR = satu topik, kecil. Lebih mudah ditinjau, lebih jarang bentrok.
- Ragu? Tanya di grup sebelum mengubah berkas milik orang lain.
