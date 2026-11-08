# Panduan API untuk alat (ESP32) — EWS Longsor Desa Lumeneng

Dokumen ini untuk yang membuat alatnya. Website sudah siap menerima 4 sensor:
kelembapan tanah, intensitas hujan, kemiringan, dan kelembapan udara.

> Perubahan 5-11-2026: field `curah_hujan` (mm/jam) DIGANTI `intensitas_hujan` (0–100 %) karena alat memakai
> sensor hujan MH-RD yang hanya mengukur basah/kering, bukan jumlah hujan. Kiriman yang hanya berisi
> `curah_hujan` kini ditolak (422). Langkah memasang alat: `docs/ews/PANDUAN_ESP32.md`.

## 1. Yang harus disiapkan dulu (di sisi website)

1. Isi `EWS_API_KEY` di file `.env` website dengan teks acak yang panjang (mis. 32 karakter).
   Teks yang sama dipasang di kode ESP32. Kalau kosong, semua kiriman ditolak (HTTP 401).
2. Daerah (wilayah) harus sudah ada di halaman **Admin → Daftar wilayah**.
   Kolom **ID alat** di tabel itu dipakai ESP32 untuk menyebut "saya alat di daerah mana".
3. Jalankan `php artisan migrate` sekali setelah file baru ditimpa (menambah kolom sensor baru).

## 2. Alamat

| Fungsi | Metode | Alamat |
|---|---|---|
| Kirim data sensor | `POST` | `https://DOMAIN-KAMU/api/sensor` |
| Tes koneksi | `GET` | `https://DOMAIN-KAMU/api/ping` |

Wajib header: `X-API-KEY: <isi EWS_API_KEY>`
Untuk POST: `Content-Type: application/json` dan `Accept: application/json`.

Pakai **https**. Kalau memakai http dan hosting mengalihkan ke https, kiriman POST berubah
jadi GET dan datanya hilang tanpa pesan jelas.

## 3. Isi kiriman (JSON)

```json
{
  "wilayah_id": 1,
  "kelembapan_tanah": 63.4,
  "intensitas_hujan": 45.5,
  "kemiringan": 3.2,
  "kelembapan_udara": 88.0,
  "kabel_putus": false
}
```

| Field | Wajib? | Satuan | Rentang | Keterangan |
|---|---|---|---|---|
| `wilayah_id` | ya* | angka | — | ID dari Admin → Daftar wilayah. Alternatif: `lokasi` (nama persis, huruf besar-kecil dan spasi harus sama) |
| `kelembapan_tanah` | tidak | % | 0 – 100 | Nama lama `kelembapan` juga diterima |
| `intensitas_hujan` | tidak | % | 0 – 100 | Seberapa basah papan sensor hujan: 0 = kering, 100 = basah kuyup (lihat bagian 5) |
| `kemiringan` | tidak | derajat | −90 – 90 | Selisih sudut dari posisi saat alat dipasang (negatif/positif sama saja, yang dinilai nilai mutlaknya) |
| `kelembapan_udara` | tidak | % | 0 – 100 | Hanya ditampilkan, tidak ikut menentukan status |
| `kabel_putus` | tidak | true/false | — | Kirim `true` jika kabel EWS putus → wilayah langsung **AWAS** |

\* Salah satu dari `wilayah_id` atau `lokasi`.

Aturan penting:

- Kirim **minimal satu** nilai sensor (atau `kabel_putus: true`).
- Angka boleh desimal (`63.4`) memakai **titik**, bukan koma.
- Kalau satu sensor rusak/gagal baca, **jangan kirim fieldnya** (atau kirim `null`). Jangan kirim `0`
  atau `-999` sebagai tanda rusak, karena akan dianggap bacaan sungguhan. Data sensor lain tetap tersimpan.
- Kirim berkala, disarankan **tiap 1–5 menit**. Jika tidak ada kiriman 15 menit, dashboard menandai
  alat "tidak mengirim data".
- Batas kiriman: 60 permintaan per menit per alamat IP.

## 4. Balasan server

| Kode | Arti | Yang dilakukan alat |
|---|---|---|
| `201` | Tersimpan | Selesai |
| `401` | `X-API-KEY` salah / belum diisi di `.env` | Periksa kunci, jangan kirim ulang terus-menerus |
| `422` | Data tidak valid (isi `errors` menjelaskan) | Perbaiki format; kirim ulang tidak akan membantu |
| `429` | Terlalu sering | Kirim lebih jarang |
| `5xx` / timeout | Server/sinyal bermasalah | Coba lagi nanti (mis. 30 detik, 1 menit, 2 menit) |

Contoh balasan `201` (ringkas). Field `telegram_terkirim` selalu `null` dan hanya dipertahankan
agar kontrak untuk firmware tidak berubah:

```json
{ "pesan": "Data tersimpan", "menunggu_validasi": false, "telegram_terkirim": null,
  "waktu_server": "2026-11-03T13:05:10+07:00", "data": { "id": 42, "status": "Aman", "warna": "hijau" } }
```

Jam data **diambil dari jam server**, jadi ESP32 tidak perlu RTC/NTP.

## 5. Penjelasan satuan (supaya angka alat dan ambang website sama)

- **Intensitas hujan**: persentase basah papan sensor MH-RD. Alat membaca nilai analog (AO), lalu mengubahnya
  ke 0–100 % memakai nilai papan kering dan papan basah yang diukur sendiri (lihat `PANDUAN_ESP32.md`).
  Ambang awal: waspada 40 %, validasi 70 % — SEMENTARA, tentukan bersama tim setelah alat diuji saat hujan.
  Sensor ini tidak bisa mengukur mm hujan.
- **Kemiringan**: ambang awal 5° (waspada) dan 15° (validasi) hanya angka **sementara**.
  Tentukan angka sebenarnya bersama tim setelah alat dikalibrasi, lalu ubah di Admin → Ambang batas sensor.
- **Kelembapan tanah**: ambang awal 50% dan 80%. Kalibrasi sensor (nilai kering = 0%, basah = 100%) dilakukan di alat.

## 6. Bagaimana status ditentukan

- Semua sensor di bawah batas waspada → **Aman** (hijau)
- Salah satu sensor ≥ batas waspada → **Waspada** (kuning)
- Salah satu sensor ≥ batas validasi → tetap Waspada, tetapi **admin diminta memvalidasi** (laporan baru tidak dibuat
  berulang selama laporan menunggu atau baru diputuskan, 30 menit)
- **Awas** (merah) hanya dari `kabel_putus: true` atau setelah admin menyetujui

## 7. Tes tanpa alat (dari laptop)

```bash
curl -i https://DOMAIN-KAMU/api/ping -H "X-API-KEY: ISI_KUNCI"

curl -i -X POST https://DOMAIN-KAMU/api/sensor \
  -H "X-API-KEY: ISI_KUNCI" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"wilayah_id":1,"kelembapan_tanah":63.4,"intensitas_hujan":45.5,"kemiringan":3.2,"kelembapan_udara":88}'
```

## 8. Catatan untuk modem

- **Modem WiFi/MiFi**: ESP32 tinggal tersambung ke WiFi modem; pakai `esp32_contoh.ino` apa adanya.
- **Modul GSM (SIM800L/SIM7600 dsb.)**: pakai library TinyGSM; isi JSON dan header-nya sama.
  SIM800L lemah untuk HTTPS modern — modul 4G (SIM7600/A7670) lebih aman. Kalau terpaksa memakai HTTP
  murni, pastikan hosting tidak memaksa redirect ke https untuk alamat `/api/*`.
- Catu daya modem sering menjadi penyebab restart. Siapkan sumber arus yang cukup dan pertimbangkan watchdog.
- Simpan data di alat bila sinyal putus, lalu kirim saat tersambung kembali (kirim satu per satu, urut dari yang lama).
