# Firmware alat (ESP32)
`esp32_contoh/esp32_contoh.ino` mengirim data ke web lewat MQTT (`PAKAI_MQTT = true`, disarankan) atau
`POST /api/sensor` (`PAKAI_MQTT = false`), untuk alat dengan 2 modul LM393 (tanah + hujan), MPU-6050, dan DHT22.
- Pengiriman MQTT dan tes: `docs/ews/PANDUAN_MQTT.md`. Kontrak HTTP: `docs/ews/API_ALAT.md`.
- Kabel dan kalibrasi sensor: `docs/ews/PANDUAN_ESP32.md`.
Isi `WIFI_PASS`, `MQTT_PASS`, dan `API_KEY` HANYA di salinan lokal; jangan commit nilai asli.

`ews_lumeneng/ews_lumeneng.ino` adalah firmware alat lapangan (HTTP, limit switch + sirene lewat relay).
Alat tidak menyimpan status Awas: limit switch tergeser 3 detik -> sirene bunyi `SIRENE_DURASI_MS` dan
`kabel_putus: true` dikirim; web yang menahan Awas sampai admin mencabutnya, dan pencabutan itu ikut
menghentikan sirene. Nama WiFi, kata sandi, dan `API_KEY` ada di `rahasia.h` (diabaikan Git;
salin dari `rahasia.contoh.h`).
