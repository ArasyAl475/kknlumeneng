# Aturan domain EWS Longsor (satu sumber kebenaran)

Diturunkan dari prototipe. Jika ingin mengubah aturan di sini, bicarakan dengan tim
(termasuk Karang Taruna) dan perbarui dokumen ini DAN tesnya dalam PR yang sama.

## Sensor
| Sensor | Field API | Satuan | Ikut menentukan status? |
|---|---|---|---|
| Kelembapan tanah | `kelembapan_tanah` (alias lama `kelembapan`) | % (0–100) | Ya |
| Curah hujan | `curah_hujan` | mm per jam (hujan 1 jam terakhir) | Ya |
| Kemiringan | `kemiringan` | derajat dari posisi saat dipasang; nilai mutlak yang dinilai | Ya |
| Udara | `kelembapan_udara` (asumsi, lihat keputusan terbuka di PLAN.md) | % | Tidak, hanya ditampilkan |

Sensor yang gagal baca: field dikosongkan (`null`/tidak dikirim). Jangan simpan `0` atau `-999`.
Tampilan: "tidak ada data", bukan 0.

## Ambang awal (diubah admin lewat halaman admin; disimpan di `ews_pengaturan`)
| Sensor | Waspada | Validasi |
|---|---|---|
| Kelembapan tanah | ≥ 50 % | ≥ 80 % |
| Curah hujan | ≥ 10 mm/jam (lebat) | ≥ 20 mm/jam (sangat lebat); acuan BMKG |
| Kemiringan | ≥ 5° (SEMENTARA) | ≥ 15° (SEMENTARA); wajib dikalibrasi di lapangan |

`config/ews.php` hanya menyimpan nilai awal untuk seeder. Setelah itu sumbernya basis data.
Validasi saat menyimpan ambang: batas validasi harus lebih besar dari batas waspada.

## Level per sensor
`null` = sensor tidak mengirim, `0` = normal, `1` = waspada, `2` = melewati batas validasi.

## Status wilayah (hasil `PenilaianStatus`)
1. `kabel_putus = true` → **Awas** (merah), otomatis, Telegram dikirim sekali, status ditahan.
2. Ada sensor level 2 → **Waspada** (kuning) + laporan **menunggu validasi admin**.
3. Ada sensor level 1 → **Waspada** (kuning).
4. Selain itu → **Aman** (hijau).

**Awas hanya berasal dari dua hal: kabel putus (otomatis) atau persetujuan admin.**
Jangan menjadikan angka sensor saja sebagai pemicu Awas otomatis tanpa keputusan tim:
manusia tetap di dalam lingkaran keputusan.

## Alur validasi
- Alat terus mengirim data; admin diminta memvalidasi SEKALI, bukan tiap kiriman.
- Tidak membuat laporan "menunggu" baru jika: sudah ada yang menunggu untuk wilayah itu,
  wilayah sudah Awas, atau admin baru memutuskan dalam `EWS_VALIDASI_JEDA_MENIT` (30 menit).
- Setuju → pembacaan jadi Awas, wilayah `status_awas = true`, Telegram dikirim sekali.
- Tolak → status wilayah tidak berubah.
- Awas ditahan sampai admin **mencabutnya** (dengan konfirmasi). Setelah dicabut, status
  kembali mengikuti data sensor.
- Status utama dashboard = kondisi terburuk dari semua wilayah.

## Alat offline
Tanpa kiriman ≥ `EWS_ALAT_OFFLINE_MENIT` (15 menit) → tandai "tidak mengirim data" di dashboard
dan halaman status alat admin. (Opsional: Telegram sekali saat offline, butuh scheduler.)
Waktu data memakai jam server (alat tidak perlu RTC/NTP).

## Telegram
Token dan chat id hanya dari `.env`. Jenis pesan: perlu validasi, Awas (kabel putus), Awas
(disetujui admin). Berisi lokasi, semua sensor yang tersedia, pemicu, waktu, dan tautan.
Anti-spam per lokasi untuk pesan "perlu validasi". Gagal kirim dicatat ke log (tanpa token)
dan tidak boleh menggagalkan penyimpanan data.

## Kontrak API alat
Lihat `docs/ews/API_ALAT.md`. Tidak boleh berubah tanpa persetujuan pembuat alat.
Endpoint: `POST /api/sensor`, `GET /api/ping`, header `X-API-KEY`.
