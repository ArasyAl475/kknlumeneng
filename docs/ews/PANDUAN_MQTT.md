# Panduan MQTT untuk alat EWS

MQTT adalah jalur kedua pengiriman data alat, di samping `POST /api/sensor` (HTTP, tetap berfungsi).
Isi datanya sama dengan `API_ALAT.md`. Panduan lama `PANDUAN_ESP32.md` tetap berlaku untuk kabel dan kalibrasi sensor;
bagian pengiriman datanya digantikan dokumen ini.

Istilah: **broker** = "kantor pos" yang menerima pesan dari alat dan meneruskannya ke web.
**Topik** = alamat pesan. **Publish** = alat menerbitkan pesan. **Subscribe** = web berlangganan pesan.

```
ESP32 --(publish, TLS)--> BROKER --(subscribe)--> php artisan ews:mqtt --> database --> dashboard
                                    <--(balasan: diterima / alasan ditolak)--
```

## 1. Pilihan broker

**Rekomendasi: EMQX Cloud Serverless.** Alasannya: tidak ada server yang harus dirawat (hosting web belum
diputuskan), memakai TLS, dan punya kuota gratis. HiveMQ Cloud dicoret karena ternyata berbayar.

Kuota gratis EMQX Serverless menurut dokumentasi resminya: **1 juta "menit sesi" dan 1 GB lalu lintas per bulan**,
dengan syarat batas pengeluaran bulanan (*monthly spend limit*) diatur ke **0**.
Satu menit sesi = satu koneksi aktif selama satu menit. Hitungan kasar:

- Satu koneksi yang menyala terus sebulan ≈ 43.200 menit sesi. Web (1 koneksi) + alat (1 koneksi per alat)
  ⇒ kuota cukup untuk kira-kira **23 koneksi: web + sekitar 22 alat**. Untuk desa ini lebih dari cukup.
- Lalu lintas: satu alat yang mengirim tiap menit memakai puluhan MB per bulan, jauh di bawah 1 GB.
- **Bila kuota habis, layanan dihentikan sampai bulan berikutnya** (bukan ditagih, bila spend limit 0).
  Untuk sistem peringatan dini ini penting: pantau pemakaian di halaman Overview deployment, dan ingat bahwa
  jalur HTTP (`API_ALAT.md`) tetap hidup sebagai cadangan. Alat tinggal diubah ke `PAKAI_MQTT = false`.
- Syarat gratis bisa berubah. Cek halaman harga resmi EMQX sebelum dipasang tetap.

| Pilihan | Cocok bila | Kekurangan |
|---|---|---|
| **EMQX Cloud Serverless** (disarankan) | Belum punya server sendiri | Layanan pihak ketiga; kuota bulanan, habis = berhenti |
| Mosquitto di VPS sendiri | Punya VPS dan mau mengelola sendiri | Harus memasang TLS, akun, pembaruan keamanan sendiri |
| Mosquitto di laptop | Hanya untuk tes lokal (bagian 4B) | Tidak bisa dipakai di lapangan |

Kode web dan alat memakai MQTT standar, jadi pindah broker nanti cukup mengganti host, akun, dan sertifikat.

## 2. Siapkan broker (EMQX Cloud Serverless; tampilan situs bisa berubah)

1. Daftar di situs EMQX Cloud, buat deployment **Serverless**. Saat membuat, biarkan **monthly spend limit = 0**
   agar tidak pernah ditagih.
2. Dari halaman **Overview** deployment, catat **alamat (host)** dan **port TLS** (biasanya 8883).
   Bila halaman itu menyediakan **CA certificate** untuk diunduh, unduh dan simpan (dipakai di langkah D dan di bagian 3).
3. Buat akun di menu **Authentication** (username + password). Buat **dua akun terpisah**:
   `ews-web` untuk web, dan `ews-alat-1` untuk alat wilayah 1 (tambah satu akun per alat lain).
4. Atur izin di menu **Authorization** (daftar aturan per username). Aturan bawaan EMQX **membolehkan semua**
   sampai kamu menyalakan mode "whitelist": di tab *All Users* tambah aturan topik `#`, aksi *Publish & Subscribe*,
   izin *Deny*. Setelah itu tambahkan aturan *Allow* berikut:

   | Username | Topik | Aksi |
   |---|---|---|
   | `ews-web` | `ews/+/sensor` | Subscribe |
   | `ews-web` | `ews/+/balasan` | Publish |
   | `ews-alat-1` | `ews/1/sensor` | Publish |
   | `ews-alat-1` | `ews/1/balasan` | Subscribe |

   Dengan begitu satu alat tidak bisa menulis ke wilayah lain. Bila ada aturan yang ditolak saat dites, periksa
   penulisan topik dan dokumentasi *Authorization* EMQX Cloud.
5. Simpan nama pengguna dan kata sandi di tempat aman. Jangan menempelkannya ke chat atau Git.

## 3. Siapkan web

1. Di `.env` isi (nilai rahasia diketik sendiri, jangan di-commit):
   ```
   EWS_MQTT_AKTIF=true
   EWS_MQTT_HOST=alamat-dari-halaman-overview-emqx
   EWS_MQTT_PORT=8883
   EWS_MQTT_TLS=true
   EWS_MQTT_USERNAME=ews-web
   EWS_MQTT_PASSWORD=...
   ```
   Bila muncul galat sertifikat (umum di Windows/XAMPP, atau bila EMQX memakai CA sendiri), isi `EWS_MQTT_CA_FILE`
   dengan path berkas CA yang diunduh dari halaman Overview EMQX (langkah 2 bagian 2). Untuk XAMPP bisa juga memakai
   `cacert.pem` dari curl.se/ca.
2. Jalankan `php artisan migrate` (bila belum). Perintah MQTT tidak butuh tabel baru.
3. Coba: `php artisan ews:mqtt`. Hasil yang benar: `Tersambung ke ...:8883, menunggu data di ews/+/sensor`.
   Tekan Ctrl+C untuk berhenti.
4. Pilih cara menjalankan di server (pilih satu):
   - **A. Server dengan terminal (VPS)**: jalankan terus-menerus dengan Supervisor, `EWS_MQTT_JADWAL=false`.
     ```
     [program:ews-mqtt]
     command=php /path/ke/proyek/artisan ews:mqtt
     autostart=true
     autorestart=true
     stopwaitsecs=10
     redirect_stderr=true
     stdout_logfile=/var/log/ews-mqtt.log
     ```
   - **B. Hosting yang hanya punya cron**: set `EWS_MQTT_JADWAL=true` dan pasang cron tiap menit
     `* * * * * php /path/ke/proyek/artisan schedule:run`. Tiap menit web berlangganan ±50 detik;
     pesan di sela-sela ditampung broker lalu dikirim saat web tersambung lagi.
     Jangan menyalakan A dan B bersamaan: dua koneksi dengan id yang sama saling menendang.
   Waktu data yang tampil adalah **waktu web menerima pesan**, bukan waktu alat mengirim.

## 4. Cara mengetes

### A. Tes otomatis (tanpa broker, tanpa alat)
`php artisan test --filter=MqttTest` → semua hijau. Ini menguji aturan penerimaan: pesan valid, JSON rusak,
di luar rentang, wilayah tidak ada, topik salah, terlalu sering, dan kabel putus.

### B. Tes di laptop dengan broker lokal (tanpa alat)
1. Pasang Mosquitto dari mosquitto.org (Windows installer), lalu jalankan `mosquitto -v` di satu terminal.
   Tanpa konfigurasi, ia hanya menerima sambungan dari laptop itu sendiri.
2. `.env` lokal: `EWS_MQTT_AKTIF=true`, `EWS_MQTT_HOST=127.0.0.1`, `EWS_MQTT_PORT=1883`, `EWS_MQTT_TLS=false`,
   `EWS_MQTT_USERNAME=` dan `EWS_MQTT_PASSWORD=` dikosongkan. (Tanpa TLS hanya boleh di lokal; di produksi perintah menolak.)
3. Terminal 2: `php artisan ews:mqtt`.
4. Terminal 3 (PowerShell), pantau balasan: `mosquitto_sub -h 127.0.0.1 -t "ews/+/balasan" -v`
5. Terminal 4, kirim data. Tulis isi ke berkas dulu karena tanda kutip di PowerShell sering merusak JSON:
   ```
   Set-Content -Encoding ascii payload.json '{"kelembapan_tanah":55.5,"intensitas_hujan":20,"kemiringan":1.2,"kelembapan_udara":80}'
   mosquitto_pub -h 127.0.0.1 -t ews/1/sensor -q 1 -f payload.json
   ```
   (angka `1` pada topik = ID alat dari Admin → Daftar wilayah)
6. Yang harus terlihat:
   - Terminal 2: `[tanggal jam] ews/1/sensor -> tersimpan (Waspada)`
   - Terminal 3: `ews/1/balasan {"diterima":true,"status":"Waspada","alasan":null}`
   - Browser: `/ews` menampilkan wilayah itu Waspada, dan Admin → Riwayat bertambah satu baris.
7. Uji penolakan: kirim `{"kelembapan_tanah":150}` → balasan `diterima:false` dengan alasan, dan tidak ada baris baru.

Cara ini sudah saya jalankan dengan broker uji sementara: pesan valid tersimpan, sedangkan data di luar rentang,
bukan JSON, wilayah tidak ada, dan topik salah ditolak dan tidak tersimpan; kabel putus menjadi Awas.

### C. Tes dengan broker cloud tanpa alat
Pakai aplikasi MQTTX (atau klien MQTT di konsol EMQX bila ada): sambungkan memakai akun `ews-alat-1` (host, port TLS, CA bila diminta),
lalu publish ke `ews/1/sensor` dengan isi JSON seperti di atas. Hasil yang terlihat sama seperti B.

### D. Tes dengan ESP32
1. Di `esp32_contoh.ino`: `PAKAI_MQTT = true`, isi `MQTT_HOST`, `MQTT_USER`, `MQTT_PASS`, `WILAYAH_ID`, dan ikuti kalibrasi di `PANDUAN_ESP32.md` bagian E.
   Atur `MODE_KALIBRASI = false`. Pasang library **PubSubClient** (Nick O'Leary) di Library Manager.
2. Pastikan `php artisan ews:mqtt` berjalan di web.
3. Upload, buka Serial Monitor (115200). Tiap menit harus muncul:
   `MQTT ews/1/sensor {...} -> terkirim` lalu `BALASAN web: {"diterima":true,"status":"Aman",...}`.
4. Uji sensor satu per satu seperti `PANDUAN_ESP32.md` bagian F (tanah basah → Waspada, miringkan MPU, dst.).

## 5. Untuk pembuat alat (teman yang membuat alatnya)

Yang perlu alat lakukan hanya menerbitkan JSON ke broker. Jangan pakai `curah_hujan` lagi; nama barunya `intensitas_hujan`.

| Hal | Nilai |
|---|---|
| Broker | host dan akun diberikan pengelola web (satu akun per alat), port **8883**, **TLS** |
| Topik terbit | `ews/<ID alat>/sensor` (ID dari Admin → Daftar wilayah) |
| Topik balasan (opsional dibaca) | `ews/<ID alat>/balasan` |
| QoS / retain | QoS 0 atau 1, **retain = false** (wajib; pesan retained diabaikan web) |
| Interval | 1–5 menit; web mengabaikan pesan dari wilayah yang sama lebih rapat dari 5 detik |
| Client ID | unik per alat, mis. `ews-alat-1` (dua alat dengan id sama saling menendang) |
| Ukuran | di bawah 2 KB; `PubSubClient` perlu `setBufferSize(512)` karena bawaannya 256 byte |

Isi pesan (JSON; titik sebagai desimal; sensor yang gagal dibaca **jangan dikirim**, bukan 0 atau -999):

```json
{"kelembapan_tanah":63.4,"intensitas_hujan":45.5,"kemiringan":3.2,"kelembapan_udara":88.0,"kabel_putus":false}
```

| Field | Rentang | Keterangan |
|---|---|---|
| `kelembapan_tanah` | 0–100 % | |
| `intensitas_hujan` | 0–100 % | 0 = papan kering, 100 = basah kuyup |
| `kemiringan` | −90–90 ° | selisih dari posisi pasang |
| `kelembapan_udara` | 0–100 % | hanya ditampilkan |
| `kabel_putus` | true/false | `true` → wilayah langsung **Awas** |

Minimal satu sensor atau `kabel_putus:true`. `wilayah_id` di isi boleh dikirim, tetapi **harus sama dengan angka di topik**;
bila beda, pesan ditolak. Wilayah selalu ditentukan oleh topik.

**Balasan.** MQTT tidak punya kode `201`/`422`. Web menerbitkan balasan ke `ews/<ID>/balasan` setiap kali memproses pesan:
`{"diterima":true,"status":"Aman","alasan":null}` atau `{"diterima":false,"status":null,"alasan":"Data tidak valid: ..."}`.
Alat boleh mencetaknya ke Serial untuk debugging (kode contoh sudah melakukannya). `publish` yang sukses hanya berarti
broker menerima pesan, belum tentu web menerimanya.

**Batas yang perlu diketahui.** `PubSubClient` hanya bisa menerbitkan QoS 0. Bila alat offline, data pada waktu itu hilang
(tidak ada antrean di alat). Di sisi web, pesan QoS 1 yang tiba saat web tidak berlangganan ditampung broker.
Waktu data memakai jam web saat menerima, jadi alat tidak perlu jam.

**TLS.** Bila `MQTT_CA_CERT` di firmware dikosongkan, alat memakai `setInsecure()` (tidak memeriksa sertifikat) dan
hanya boleh untuk uji. Untuk pemasangan tetap, tempel isi berkas CA dari halaman Overview EMQX ke `MQTT_CA_CERT`.
Dengan sertifikat, alat otomatis menyamakan jam lewat internet (NTP) dulu karena pemeriksaan sertifikat butuh tanggal;
jadi jaringannya harus mengizinkan akses NTP. Jika alat terus gagal tersambung setelah CA diisi, cek pesan di Serial Monitor.

## 6. Bila ada masalah

| Gejala | Penyebab umum |
|---|---|
| `ews:mqtt`: "Gagal tersambung ke broker" | Host/port salah, TLS tidak cocok (8883 harus `EWS_MQTT_TLS=true`), akun salah, atau galat sertifikat di Windows (isi `EWS_MQTT_CA_FILE`) |
| `ews:mqtt`: "belum diaktifkan" | `EWS_MQTT_AKTIF=false` di `.env` |
| `ews:mqtt`: "Di produksi MQTT wajib memakai TLS" | `EWS_MQTT_TLS=false` di server produksi |
| Alat serial: `MQTT gagal tersambung, kode=5` | Akun alat ditolak (kata sandi/izin salah). `kode=-2` = broker/port tidak terjangkau |
| Balasan "Topik tidak dikenal" | Topik bukan `ews/<angka>/sensor` |
| Balasan "Wilayah tidak dikenal" | ID di topik tidak ada di Daftar wilayah, atau wilayahnya sudah dihapus |
| Balasan "Terlalu sering" | Interval kirim di bawah 5 detik |
| Balasan "wilayah_id di isi tidak sama dengan topik" | `WILAYAH_ID` di isi dan di topik berbeda |
| Data tidak muncul, tidak ada balasan | `ews:mqtt` tidak berjalan di web (cek Supervisor atau cron), atau akun web tidak punya izin subscribe |
| Balasan hanya "Pesan retained diabaikan" | Alat menerbitkan dengan retain=true; ubah ke false |

## 7. Keamanan

- Selalu TLS di luar laptop. Tiga akun terpisah: web, tiap alat, dan akun admin broker (jangan dipakai di kode).
- Kata sandi hanya di `.env` dan di salinan lokal firmware. Tidak ada kata sandi yang dicatat web ke log.
- Web hanya percaya topik, bukan isi pesan, untuk menentukan wilayah. Izin broker per topik (bila tersedia)
  mencegah satu alat menulis ke wilayah lain.
- Status **Awas** tetap hanya dari kabel putus atau persetujuan admin; MQTT tidak mengubah aturan itu.
