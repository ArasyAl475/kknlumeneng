# Notifikasi HP warga (Firebase Cloud Messaging)

Server mengirim peringatan ke aplikasi Android **EWS Lumeneng** lewat Firebase Cloud Messaging (FCM).
Karena Android sendiri yang menampilkan notifikasi, peringatan tetap muncul walau aplikasi tertutup, selama HP
tersambung internet. Tanpa kiriman server, notifikasi hanya muncul saat warga membuka aplikasi.

```text
Alat ESP32 / admin → Laravel (status wilayah berubah) → FCM (topik warga-lumeneng) → semua HP warga
```

## Kapan dikirim

Status yang dinilai **sama persis** dengan yang dilihat warga di `/ews` (`DataDasbor`), lalu dibandingkan dengan
status terakhir yang sudah dikabarkan (kolom `ews_wilayah.status_notifikasi`).

| Status baru wilayah | Dikirim? | `data.status` | Suara (`channel_id`) |
|---|---|---|---|
| Awas (kabel putus, admin menyetujui, Nyatakan Awas) | Ya, selalu, tanpa jeda | `awas` | `ews_awas_v3` |
| Waspada | Ya, paling sering sekali per 10 menit per wilayah | `waspada` | `ews_waspada_v3` |
| Alat berhenti mengirim data (offline, dan wilayah tidak sedang Awas) | Ya, paling sering sekali per 10 menit | `offline` | `ews_waspada_v3` |
| Aman | Tidak (aplikasi belum punya saluran senyap) | | |

- Hanya **perubahan** yang dikirim. Kiriman alat berikutnya dengan status yang sama tidak membuat HP berbunyi lagi.
- Pemeriksaan pertama setelah fitur dipasang hanya mencatat status yang sedang berlangsung, tidak mengirim.
- Pemicu: setiap data dashboard berubah (`DataDasbor::lupakan()`: kiriman alat lewat HTTP atau MQTT, keputusan
  admin, ubah ambang), ditambah penjadwal `ews:pantau-notifikasi` tiap menit untuk alat yang berhenti mengirim.
- Di permintaan web, pengiriman dijalankan **setelah** balasan ke alat terkirim, jadi alat tidak menunggu Firebase.
- Gagal kirim (internet server/Firebase bermasalah) dicatat di log (`EWS push gagal`) dan dicoba lagi paling
  lambat semenit kemudian. Notifikasi tidak pernah menggagalkan penyimpanan data sensor.

**Penting:** `EWS_ALAT_OFFLINE_MENIT` harus lebih besar dari jarak kirim alat saat Aman. Firmware
`firmware/ews_lumeneng` mengirim tiap 30 menit saat Aman, jadi isi `EWS_ALAT_OFFLINE_MENIT=45`. Bila dibiarkan 15,
alat dianggap offline di sela setiap kiriman dan HP warga mendapat kabar "tidak mengirim data" tiap 30 menit.

## Isi pesan

Judul memakai nama wilayah di **Admin → Daftar wilayah**. Agar warga mengenalinya, beri nama yang sama dengan
nama alat di aplikasi (mis. `EWS-02 Sikembang`). `data.wilayah_id` sama dengan `wilayah[].id` di `/api/ews/data`.

| Status | Judul | Isi |
|---|---|---|
| Awas | `Awas: <nama>` | `<pemicu>. Segera menjauh dari lereng dan ikuti arahan petugas. (20.33 WIB)` |
| Waspada | `Waspada: <nama>` | `<pemicu>. Siapkan diri dan pantau kabar dari petugas. (20.33 WIB)` |
| Offline | `<nama> tidak mengirim data` | `Kondisi sekarang belum diketahui, jangan anggap sudah aman. (20.33 WIB)` |

Bentuk lengkap pesan (prioritas tinggi, ttl 6 jam, tag per wilayah, terlihat di layar kunci) ada di
`NotifikasiWarga::pesan()` dan mengikuti kontrak aplikasi. Teks peringatan sebaiknya disepakati dengan perangkat desa.

## Pengaturan `.env`

| Isian | Nilai |
|---|---|
| `EWS_PUSH_AKTIF` | `true` untuk mengirim |
| `FIREBASE_CREDENTIALS` | jalur lengkap berkas kunci akun layanan Firebase (JSON), **di luar folder proyek** |
| `EWS_PUSH_TOPIK` | bawaan `warga-lumeneng` (harus sama dengan aplikasi) |
| `EWS_PUSH_JEDA_MENIT` | bawaan `10` |
| `EWS_PUSH_OFFLINE` | bawaan `true`; `false` bila kabar "tidak mengirim data" terlalu sering |

Berkas kunci Firebase bisa mengirim notifikasi ke semua HP warga. Jangan masukkan ke Git, jangan taruh di
`public/`, jangan kirim lewat grup. Bila bocor: Firebase Console → Project settings → Service accounts → hapus
kunci itu, buat kunci baru, ganti berkasnya di server.

Paket `kreait/laravel-firebase` sengaja tidak dipakai: cukup HTTP client Laravel (`App\Services\Ews\PushFcm`),
seperti sinkron Google Sheets, sehingga `composer.json` bersama tidak berubah.

## Perintah

| Perintah | Fungsi |
|---|---|
| `php artisan ews:uji-notifikasi awas --periksa-saja` | Firebase memeriksa kunci dan isi pesan. **Tidak ada HP yang menerima.** |
| `php artisan ews:uji-notifikasi awas` | Kirim notifikasi `[UJI]` ke **semua** HP warga (ditanya dulu). Status: `awas`, `waspada`, `offline` |
| `php artisan ews:pantau-notifikasi` | Pemeriksaan manual; biasanya dijalankan penjadwal tiap menit |

## Cara memastikan di HP

1. Buka aplikasi EWS Lumeneng versi terbaru minimal sekali dengan internet (agar berlangganan topik).
2. Tutup aplikasi dengan menggesernya dari daftar aplikasi terbaru. Jangan "Paksa berhenti" di Setelan:
   Android memblokir semua notifikasi aplikasi yang dipaksa berhenti.
3. Jalankan `php artisan ews:uji-notifikasi awas`. Notifikasi muncul dalam beberapa detik dengan suara Awas,
   juga saat layar terkunci. Ulangi dengan `waspada`: suaranya berbeda.

Tidak muncul? Cek `storage/logs/laravel.log` (`EWS push gagal`), dan pastikan aplikasi tidak dibatasi penghemat
baterai (Oppo/Realme/Vivo/Xiaomi sering membatasinya). Push butuh internet; peringatan utama di lapangan tetap
sirene. iPhone belum didukung (butuh kunci APNs dari akun Apple Developer).
