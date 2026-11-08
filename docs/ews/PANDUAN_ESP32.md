# Panduan menyambungkan alat EWS (ESP32) ke website

Untuk alat dengan: 2 modul LM393 (probe tanah YL-69 dan papan hujan MH-RD), MPU-6050 (GY-521), DHT22.
Kontrak data: `API_ALAT.md`. Kode alat: `firmware/esp32_contoh/esp32_contoh.ino`.

Istilah: **AO** = keluaran angka (analog) modul LM393. **DO** = keluaran hidup/mati; tidak dipakai di sini.
**Kalibrasi** = mengukur nilai "kering" dan "basah" supaya angka mentah bisa diubah ke persen.

## A. Siapkan website (di laptop)

1. Isi `EWS_API_KEY` di berkas `.env` dengan teks acak sepanjang 32 karakter. Jangan dibagikan ke chat atau Git.
2. Jalankan `php artisan migrate`. Hasilnya harus menyebut `2026_11_05_100000_ews_tambah_intensitas_hujan_pada_pembacaan`.
3. Login admin dan buka **Daftar wilayah**. Bila kosong, tambahkan satu wilayah (di komputer lokal boleh `php artisan db:seed --class=EwsSeeder` untuk wilayah contoh). Catat **ID alat** wilayah yang akan dipakai.
4. Buka **Ambang batas sensor** sekali. Ambang intensitas hujan memakai nilai awal sementara 40% / 70% sampai disimpan di sini.
5. Jalankan `php artisan serve --host=0.0.0.0` (agar bisa dijangkau dari ESP32 di WiFi yang sama).

## B. Tes API tanpa alat (PowerShell di laptop)

1. `curl.exe -i http://localhost:8000/api/ping -H "X-API-KEY: KUNCI_KAMU"` → harus `200`. Tanpa header kunci → `401`.
2. Kirim data contoh:
   `curl.exe -i -X POST http://localhost:8000/api/sensor -H "X-API-KEY: KUNCI_KAMU" -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"wilayah_id\":1,\"kelembapan_tanah\":63.4,\"intensitas_hujan\":45.5,\"kemiringan\":3.2,\"kelembapan_udara\":88}"`
   → harus `201`.
3. Buka `http://localhost:8000/ews`. Wilayah itu harus Waspada (tanah 63,4% dan hujan 45,5% melewati batas waspada).

## C. Pasang kabel (ESP32 DevKit, matikan USB dulu)

| Komponen | Pin komponen | Sambung ke ESP32 |
|---|---|---|
| LM393 tanah | VCC | GPIO 25 |
| | GND | GND |
| | AO | GPIO 34 |
| | DO | tidak dipakai |
| LM393 hujan | VCC | GPIO 26 |
| | GND | GND |
| | AO | GPIO 35 |
| | DO | tidak dipakai |
| MPU-6050 (GY-521) | VCC | 3V3 |
| | GND | GND |
| | SDA | GPIO 21 |
| | SCL | GPIO 22 |
| | AD0, INT, XDA, XCL | tidak dipakai (AD0 dibiarkan = alamat 0x68) |
| DHT22 | + | 3V3 |
| | - | GND |
| | out | GPIO 4 |

Probe YL-69 dan papan MH-RD disambung ke modul LM393-nya lewat kabel 2 pin bawaan.

Catatan:
- VCC modul LM393 diambil dari pin GPIO supaya probe hanya dialiri listrik saat dibaca (probe yang terus dialiri cepat berkarat). Bila tidak mau, sambung VCC ke 3V3 dan isi `PIN_TANAH_DAYA` / `PIN_HUJAN_DAYA` dengan `-1` di kode.
- Pakai VCC 3,3 V, jangan 5 V: keluaran AO ikut naik sampai VCC, sedangkan pin ESP32 hanya tahan 3,3 V.
- DHT22 bentuk papan 3 pin biasanya sudah punya resistor. Bila hanya sensor kosong, pasang resistor 10 kΩ antara pin out dan +.

## D. Siapkan Arduino IDE (sekali saja)

1. Pasang Arduino IDE 2.x. Di **File → Preferences → Additional boards manager URLs**, tambahkan URL paket board ESP32 dari Espressif (lihat dokumentasi resmi Espressif).
2. **Tools → Board → Boards Manager**, cari `esp32` (Espressif Systems), pasang.
3. **Tools → Manage Libraries**, pasang `DHT sensor library` (Adafruit) dan `Adafruit Unified Sensor`.
4. Pilih board `ESP32 Dev Module` dan port COM yang muncul saat ESP32 dicolok.

## E. Kalibrasi sensor (belum mengirim ke website)

1. Salin `esp32_contoh.ino` ke folder kerja lokal. Biarkan `MODE_KALIBRASI = true`. Isi WiFi, URL, kunci, dan `WILAYAH_ID`.
2. Upload, buka **Tools → Serial Monitor** (115200 baud). Tiap detik muncul baris `TANAH mentah=... | HUJAN mentah=... | MIRING=... | UDARA=...`.
3. **Tanah**: probe di udara → catat angka `TANAH mentah` (= `TANAH_KERING`). Tancapkan ke gelas air → catat (= `TANAH_BASAH`).
4. **Hujan**: papan kering → catat (= `HUJAN_KERING`). Teteskan air sampai permukaannya basah merata → catat (= `HUJAN_BASAH`).
5. **Kemiringan**: pasang MPU-6050 di tempat final dan kokoh, lalu ketik `nol` + Enter di Serial Monitor. Itu menyimpan posisi pasang sebagai 0°. Ganjal satu sisi kira-kira 10° → MIRING harus mendekati 10. Bila `MPU-6050 TIDAK terdeteksi`, periksa kabel SDA/SCL/VCC/GND.
6. **Udara**: bandingkan UDARA dengan hygrometer atau aplikasi cuaca. Selisih beberapa persen masih wajar.
7. Isi keempat konstanta di bagian `KALIBRASI`, ubah `MODE_KALIBRASI = false`, lalu upload ulang.

## F. Tes terhubung ke website

1. Cari alamat IP laptop (`ipconfig`, lihat IPv4 WiFi), misalnya `192.168.1.10`. Laptop dan ESP32 harus di WiFi yang sama; izinkan port 8000 di Windows Firewall bila diminta.
2. Untuk tes lewat http lokal, di `kirimData()` ganti `WiFiClientSecure klien;` menjadi `WiFiClient klien;`, hapus baris `klien.setInsecure();`, dan isi `URL_SENSOR = "http://192.168.1.10:8000/api/sensor"`. **Kembalikan ke https sebelum dipasang di lapangan.**
3. Upload. Di Serial Monitor harus muncul `POST {...} -> 201`.
4. Buka `/ews`: Bacaan sensor wilayah itu harus terisi empat kartu. Buka **Admin → Riwayat**: baris baru muncul tiap menit.

Uji dampaknya pada status:
- Tancapkan probe tanah ke air: kelembapan tanah naik ≥ 50% → **Waspada**; ≥ 80% → admin diminta validasi (muncul di halaman Validasi admin).
- Basahi papan hujan: intensitas hujan ≥ 40% → **Waspada**; ≥ 70% → minta validasi.
- Miringkan MPU-6050 ≥ 5° → **Waspada**; ≥ 15° → minta validasi. Nilai kemiringan dan hujan ini SEMENTARA.
- Cabut DHT22 atau MPU-6050 → kartu itu menjadi "Tidak ada data", bukan 0. Probe tanah/hujan yang lepas TIDAK selalu terdeteksi (pin kosong membaca angka acak), jadi cek lewat Serial Monitor bila angkanya mencurigakan.
- Kabel putus (bila `PIN_KABEL` diisi) → **Awas**. Admin harus mencabut Awas secara manual.
- Cabut daya ESP32: setelah 15 menit dashboard menandai "alat tidak mengirim data".

Setelah uji selesai, kembalikan ambang yang diubah di **Admin → Ambang batas** dan hapus data uji bila perlu.

## G. Bila ada masalah

| Gejala di Serial Monitor | Penyebab umum |
|---|---|
| `-2` | Belum tersambung WiFi: SSID/sandi salah atau sinyal lemah |
| `-1` atau angka negatif lain | Alamat salah, server tidak aktif, atau firewall menolak |
| `401` | `API_KEY` di alat tidak sama dengan `EWS_API_KEY` di `.env`, atau `.env` belum diisi |
| `422` | Isi tidak valid; baca bagian `errors` pada balasan (mis. `wilayah_id` tidak ada) |
| `429` | Terlalu sering; tambah `INTERVAL_KIRIM_MS` |
| Tanah/hujan selalu 0% atau 100% | Konstanta kalibrasi terbalik atau belum diisi |
| Nilai mentah acak/melompat | Kabel AO longgar atau salah pin; pastikan GPIO 34/35 |
| `MIRING` selalu `nan` | MPU tidak terbaca atau `nol` belum disimpan |

## H. Catatan pemasangan di lapangan

- Papan hujan dipasang miring ± 30° dan menghadap langit terbuka agar air mengalir; papan datar tergenang dan terbaca basah lama.
- Probe tanah ditancapkan penuh ke tanah, jauh dari batu. Jalur tembaga tetap bisa berkarat; ganti bila kusut.
- MPU-6050 dipasang kaku pada tiang/pelat yang tertanam. Bila tiang tersenggol atau dipindah, simpan ulang `nol`.
- Pakai kotak tahan air untuk ESP32 dan modul. Beri lubang kabel yang ditutup lem silikon.
- Baca juga bagian modem dan catu daya di `API_ALAT.md` (bagian 8).
