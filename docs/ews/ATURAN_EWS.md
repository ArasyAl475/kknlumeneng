# Aturan domain EWS Longsor (satu sumber kebenaran)

Jika ingin mengubah aturan di sini, bicarakan dengan tim
(termasuk Karang Taruna) dan perbarui dokumen ini DAN tesnya dalam PR yang sama.

## Sensor
| Sensor | Field API | Satuan | Ikut menentukan status? |
|---|---|---|---|
| Kelembapan tanah | `kelembapan_tanah` (alias lama `kelembapan`) | % (0–100) | Ya |
| Intensitas hujan | `intensitas_hujan` | % (0 = kering, 100 = basah kuyup); sensor hujan MH-RD, BUKAN mm/jam | Ya |
| Kemiringan | `kemiringan` | derajat dari posisi saat dipasang; nilai mutlak yang dinilai | Ya |
| Udara | `kelembapan_udara` (asumsi, belum dikonfirmasi tim) | % | Tidak, hanya ditampilkan |

Sensor yang gagal baca: field dikosongkan (`null`/tidak dikirim). Jangan simpan `0` atau `-999`.
Tampilan: "tidak ada data", bukan 0.

## Ambang awal (diubah admin lewat halaman admin; disimpan di `ews_pengaturan`)
| Sensor | Waspada | Validasi |
|---|---|---|
| Kelembapan tanah | ≥ 50 % | ≥ 80 % |
| Intensitas hujan | ≥ 40 % (SEMENTARA) | ≥ 70 % (SEMENTARA); wajib dikalibrasi di lapangan |
| Kemiringan | ≥ 5° (SEMENTARA) | ≥ 15° (SEMENTARA); wajib dikalibrasi di lapangan |

`config/ews.php` hanya menyimpan nilai awal untuk seeder. Setelah itu sumbernya basis data.
Validasi saat menyimpan ambang: batas validasi harus lebih besar dari batas waspada.

Catatan intensitas hujan: papan sensor kecil cepat penuh air, jadi angka tinggi belum tentu hujan sangat
deras. Karena itu angkanya hanya memicu Waspada dan permintaan validasi admin, tidak pernah Awas otomatis.
Kolom lama `curah_hujan` (mm/jam) tidak lagi dipakai; datanya tetap ada di basis data sebagai riwayat.

## Level per sensor
`null` = sensor tidak mengirim, `0` = normal, `1` = waspada, `2` = melewati batas validasi.

## Status wilayah (hasil `PenilaianStatus`)
1. `kabel_putus = true` → **Awas** (merah), otomatis, status ditahan.
2. Ada sensor level 2 → **Waspada** (kuning) + laporan **menunggu validasi admin**.
3. Ada sensor level 1 → **Waspada** (kuning).
4. Selain itu → **Aman** (hijau).

**Awas hanya berasal dari tiga hal: kabel putus (otomatis), persetujuan admin atas laporan sensor,
atau pernyataan admin sendiri ("Nyatakan Awas", lihat bawah).**
Jangan menjadikan angka sensor saja sebagai pemicu Awas otomatis tanpa keputusan tim:
manusia tetap di dalam lingkaran keputusan.

## Jenis titik di peta
- **Wilayah** (`ews_wilayah`): EWS pintar yang mengirim data sensor ke web. Satu-satunya yang punya status Aman/Waspada/Awas, ikut status utama desa, dan bisa offline. Punya batas area (poligon) dan, opsional, lokasi alat (`lintang`, `bujur`).
- **Titik** (`ews_titik`), tidak pernah mengirim data dan TIDAK ikut status utama:
  - `alarm`: EWS dengan alarm yang belum terintegrasi ke web (batas area opsional).
  - `rawan`: titik rawan longsor, cukup satu koordinat.
- Titik hanya tampil di peta dashboard (penanda beda bentuk, ada keterangan) lewat kunci `titik` di `/api/ews/data`. Kunci ini tambahan; field lama tidak berubah.
- Isian koordinat di admin menerima satu titik per baris "lintang, bujur" (format Google Maps) atau JSON `[[lintang,bujur],...]`.

## Nyatakan Awas manual
Admin bisa menyatakan Awas untuk wilayah mana pun yang belum Awas (halaman Validasi dan Awas), tanpa
laporan sensor: mis. alat berhenti saat Waspada, atau kader melihat retakan di lapangan.
- Wajib alasan (min. 5 karakter) dan centang konfirmasi. Alasan hanya masuk log aktivitas admin;
  yang tampil ke warga hanya sumbernya, "Dinyatakan admin (manual)".
- Sesudahnya sama dengan Awas lain: ditahan sampai admin mencabutnya.

## Alur validasi
- Alat terus mengirim data; admin diminta memvalidasi SEKALI, bukan tiap kiriman.
- Tidak membuat laporan "menunggu" baru jika: sudah ada yang menunggu untuk wilayah itu,
  wilayah sudah Awas, atau admin baru memutuskan dalam `EWS_VALIDASI_JEDA_MENIT` (30 menit).
- Setuju → pembacaan jadi Awas, wilayah `status_awas = true`.
- Tolak → status wilayah tidak berubah.
- Awas ditahan sampai admin **mencabutnya** (dengan konfirmasi). Setelah dicabut, status
  kembali mengikuti data sensor.
- Status utama dashboard = kondisi terburuk dari semua wilayah.

## Alat offline
Tanpa kiriman ≥ `EWS_ALAT_OFFLINE_MENIT` (15 menit) → tandai "tidak mengirim data" di dashboard
dan halaman status alat admin. Tidak ada pemberitahuan keluar (Telegram sudah dihapus).
Waktu data memakai jam server (alat tidak perlu RTC/NTP).

**Alat diam saat Waspada** (status wilayah Waspada dan alatnya offline) = "kondisi tidak diketahui".
Tidak jadi Awas otomatis (alat juga mati karena listrik/sinyal), tetapi:
- warga mendapat kalimat peringatan yang lebih keras ("jangan anggap sudah aman, hindari lereng"),
  di dashboard, daftar wilayah, dan beranda;
- halaman Validasi dan Awas menampilkan peringatan merah dan menaruh wilayah itu paling atas di
  daftar "Nyatakan Awas sendiri", agar admin memeriksa lapangan lalu memutuskan.
Aturannya ada di `PenilaianStatus::kondisiTakDiketahui()`. Alat diam saat Aman cukup peringatan biasa;
alat diam saat Awas tidak berubah (Awas sudah terkunci).

## Kontrak API alat
Lihat `docs/ews/API_ALAT.md`. Tidak boleh berubah tanpa persetujuan pembuat alat.
Endpoint: `POST /api/sensor`, `GET /api/ping`, header `X-API-KEY`.

`wilayah_id` (API) dan angka di topik MQTT adalah **ID alat** (kolom `ews_wilayah.id_alat`), dipisah dari id baris
agar admin bisa menyamakannya dengan alat yang sudah terpasang. Unik; wilayah yang dihapus melepas angkanya,
dan saat dipulihkan mendapat angka bebas terkecil.

Jalur kedua dengan isi dan aturan yang sama: MQTT, topik `ews/<ID alat>/sensor`
(lihat `docs/ews/PANDUAN_MQTT.md`). Aturan isi dipakai bersama lewat `App\Services\Ews\KirimanAlat`.
