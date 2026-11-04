---
description: Periksa semuanya sebelum membuka Pull Request dan siapkan deskripsinya
---
1. Tampilkan `git status` dan `git diff --stat origin/main...HEAD`.
2. Cek batas modul: apakah ada berkas di luar folder modul milik branch ini atau di daftar
   berkas bersama (CLAUDE.md bagian 6)? Jika ada, jelaskan dan tanya apakah disengaja.
3. Cek keamanan cepat pada perubahan: tidak ada `.env`/token/kunci, rute admin memakai
   middleware `auth` + `admin`, input divalidasi, tidak ada `{!! !!}` untuk data pengguna.
4. Jalankan `/cek`.
5. Gunakan agen `peninjau-kode` untuk tinjauan singkat atas diff.
6. Tulis deskripsi PR mengikuti `.github/pull_request_template.md`, dan berikan langkah
   terminal berurutan untuk commit dan push (jangan mendorong sendiri tanpa konfirmasi).
