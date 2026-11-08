// Firmware ESP32 -> website EWS Desa Lumeneng
// Sensor: kelembapan tanah (YL-69 + LM393), hujan (MH-RD + LM393), MPU-6050 (GY-521), DHT22.
// Panduan pemasangan lengkap: docs/ews/PANDUAN_ESP32.md
// Sensor yang gagal dibaca mengembalikan NAN -> field itu tidak dikirim (bukan dikirim 0).
//
// Library yang dipasang lewat Arduino IDE (Library Manager):
//   - "DHT sensor library" (Adafruit)  - "Adafruit Unified Sensor"
//   - "PubSubClient" (Nick O'Leary)    -> hanya dipakai bila PAKAI_MQTT = true
// Wire, Preferences, WiFi, HTTPClient sudah ikut paket board ESP32.
// Pengiriman: MQTT (disarankan, panduan docs/ews/PANDUAN_MQTT.md) atau HTTP POST (docs/ews/API_ALAT.md).

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Preferences.h>
#include <DHT.h>
#include <math.h>

// ======== PENGATURAN (isi di salinan lokal, JANGAN di-commit dengan nilai asli) ========
const char* WIFI_SSID  = "NAMA_WIFI_MODEM";
const char* WIFI_PASS  = "PASSWORD_WIFI";
const int   WILAYAH_ID = 1;                       // Admin -> Daftar wilayah (kolom ID alat)

// --- Jalur kirim ---
const bool PAKAI_MQTT = true;                     // false = kirim lewat HTTP POST

// MQTT: akun ini HANYA untuk alat ini (izin terbit ke ews/<WILAYAH_ID>/sensor). Bukan akun web.
const char* MQTT_HOST = "ISI-HOST-BROKER";        // alamat dari halaman Overview deployment EMQX
const int   MQTT_PORT = 8883;                     // port TLS (cek halaman Overview)
const char* MQTT_USER = "ISI_USERNAME_ALAT";
const char* MQTT_PASS = "ISI_PASSWORD_ALAT";
// Sertifikat CA broker (isi berkas .crt/.pem dari halaman Overview), diapit R"EOF( ... )EOF".
// Kosong = tidak memeriksa sertifikat (HANYA untuk uji coba).
const char* MQTT_CA_CERT = R"EOF()EOF";

// HTTP (hanya dipakai bila PAKAI_MQTT = false)
const char* URL_SENSOR = "https://DOMAIN-KAMU/api/sensor";
const char* API_KEY    = "ISI_SAMA_DENGAN_EWS_API_KEY";
const unsigned long INTERVAL_KIRIM_MS = 60000;    // kirim tiap 1 menit

// true  = hanya cetak nilai mentah ke Serial Monitor tiap detik, TIDAK mengirim ke website.
// Mulai dari true untuk mengukur nilai kering/basah, lalu ubah ke false setelah konstanta diisi.
const bool MODE_KALIBRASI = true;

// ======== PIN ========
// Pin AO modul LM393 harus pin ADC1 (32-39). Pin ADC2 tidak bisa dipakai saat WiFi menyala.
const int PIN_TANAH_AO  = 34;
const int PIN_HUJAN_AO  = 35;
// Probe dialiri listrik hanya saat dibaca supaya tidak berkarat (elektrolisis).
// Sambungkan VCC modul ke pin ini. Isi -1 bila VCC modul langsung ke 3V3.
const int PIN_TANAH_DAYA = 25;
const int PIN_HUJAN_DAYA = 26;
const int PIN_DHT = 4;
const int PIN_SDA = 21;
const int PIN_SCL = 22;
// Pendeteksi kabel putus (opsional). -1 = tidak dipakai. Sesuaikan logikanya dengan rangkaian.
const int PIN_KABEL = -1;
// Tombol ke GND untuk menyimpan "posisi nol" kemiringan (tahan 3 detik). -1 = tidak dipakai.
// Jangan pakai tombol BOOT (GPIO0): menahannya saat menyala membuat ESP32 masuk mode flash.
const int PIN_TOMBOL_NOL = -1;

// ======== KALIBRASI: GANTI SETELAH MENGUKUR (lihat panduan) ========
// Nilai mentah ADC 0-4095. Modul LM393 umumnya: makin basah -> nilai makin KECIL.
const int TANAH_KERING = 3300;   // probe di udara
const int TANAH_BASAH  = 1400;   // probe di gelas air
const int HUJAN_KERING = 4000;   // papan kering
const int HUJAN_BASAH  = 1500;   // papan basah kuyup

DHT dht(PIN_DHT, DHT22);
Preferences prefs;

// ======== MPU-6050 ========
const uint8_t MPU_ADDR = 0x68;                    // pin AD0 ke GND (bawaan GY-521)
bool mpuSiap = false;
bool nolAda = false;                              // posisi nol sudah pernah disimpan?
float nolX = 0, nolY = 0, nolZ = 0;               // arah gravitasi saat alat dipasang

bool mulaiMpu() {
  Wire.begin(PIN_SDA, PIN_SCL);
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x6B);                               // register PWR_MGMT_1
  Wire.write(0);                                  // bangunkan sensor dari mode tidur
  return Wire.endTransmission() == 0;
}

// Satu sampel akselerometer dalam satuan g (rentang bawaan +-2g = 16384 per g).
bool bacaAkselerasi(float& x, float& y, float& z) {
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x3B);                               // register ACCEL_XOUT_H
  if (Wire.endTransmission(false) != 0) return false;
  if (Wire.requestFrom((int)MPU_ADDR, 6) != 6) return false;
  // Dibaca berurutan satu per satu: urutan evaluasi "a | b" di C++ tidak dijamin.
  int xh = Wire.read(), xl = Wire.read();
  int yh = Wire.read(), yl = Wire.read();
  int zh = Wire.read(), zl = Wire.read();
  x = (int16_t)((xh << 8) | xl) / 16384.0f;
  y = (int16_t)((yh << 8) | yl) / 16384.0f;
  z = (int16_t)((zh << 8) | zl) / 16384.0f;
  return true;
}

// Rata-rata 20 sampel supaya getaran sesaat tidak terbaca sebagai pergeseran tanah.
bool bacaAkselerasiRata(float& x, float& y, float& z) {
  float sx = 0, sy = 0, sz = 0;
  int ok = 0;
  for (int i = 0; i < 20; i++) {
    float ax, ay, az;
    if (bacaAkselerasi(ax, ay, az)) { sx += ax; sy += ay; sz += az; ok++; }
    delay(5);
  }
  if (ok < 10) return false;
  x = sx / ok; y = sy / ok; z = sz / ok;
  return true;
}

void simpanNol() {
  float x, y, z;
  if (!mpuSiap || !bacaAkselerasiRata(x, y, z)) { Serial.println("Posisi nol GAGAL disimpan: MPU-6050 tidak terbaca"); return; }
  prefs.putFloat("nx", x); prefs.putFloat("ny", y); prefs.putFloat("nz", z); prefs.putBool("ada", true);
  nolX = x; nolY = y; nolZ = z; nolAda = true;
  Serial.println("Posisi nol kemiringan disimpan");
}

// Sudut (derajat) antara arah gravitasi sekarang dan saat dipasang. Tidak peduli sensor dipasang
// menghadap ke mana, dan tidak melenceng seiring waktu seperti giroskop.
float bacaKemiringan() {
  float x, y, z;
  if (!mpuSiap || !nolAda || !bacaAkselerasiRata(x, y, z)) return NAN;
  float m1 = sqrtf(x * x + y * y + z * z);
  float m2 = sqrtf(nolX * nolX + nolY * nolY + nolZ * nolZ);
  if (m1 < 0.5f || m2 < 0.5f) return NAN;         // bukan ~1g: sensor/kabel bermasalah
  float c = (x * nolX + y * nolY + z * nolZ) / (m1 * m2);
  c = constrain(c, -1.0f, 1.0f);
  return acosf(c) * 180.0f / PI;
}

// ======== TANAH, HUJAN, UDARA ========
int bacaRata(int pin) {
  long jumlah = 0;
  for (int i = 0; i < 16; i++) { jumlah += analogRead(pin); delay(2); }
  return (int)(jumlah / 16);
}

int bacaProbe(int pinAo, int pinDaya) {
  if (pinDaya >= 0) { digitalWrite(pinDaya, HIGH); delay(150); }   // tunggu tegangan stabil
  int mentah = bacaRata(pinAo);
  if (pinDaya >= 0) digitalWrite(pinDaya, LOW);
  return mentah;
}

float keProsen(int mentah, int kering, int basah) {
  if (mentah <= 0 || kering == basah) return NAN;                 // 0 = kabel salah/korslet
  float p = 100.0f * (kering - mentah) / (float)(kering - basah);
  return constrain(p, 0.0f, 100.0f);
}

float bacaKelembapanTanah() { return keProsen(bacaProbe(PIN_TANAH_AO, PIN_TANAH_DAYA), TANAH_KERING, TANAH_BASAH); }
float bacaIntensitasHujan() { return keProsen(bacaProbe(PIN_HUJAN_AO, PIN_HUJAN_DAYA), HUJAN_KERING, HUJAN_BASAH); }

float bacaKelembapanUdara() {
  float h = dht.readHumidity();                                   // NAN bila gagal
  return (isnan(h) || h < 0 || h > 100) ? NAN : h;
}

// ======== KIRIM ========
void tambah(String& json, const char* nama, float v) {
  if (isnan(v)) return;
  if (json.length() > 1) json += ",";                             // json selalu diawali "{"
  json += "\"" + String(nama) + "\":" + String(v, 2);             // String(float) memakai titik
}

// Isi kiriman (sama untuk HTTP dan MQTT). Lewat MQTT wilayah sudah ada di topik, jadi tidak dikirim lagi.
String rakitJson(bool denganWilayah) {
  String json = "{";
  if (denganWilayah) json += "\"wilayah_id\":" + String(WILAYAH_ID);
  tambah(json, "kelembapan_tanah", bacaKelembapanTanah());
  tambah(json, "intensitas_hujan", bacaIntensitasHujan());
  tambah(json, "kemiringan", bacaKemiringan());
  tambah(json, "kelembapan_udara", bacaKelembapanUdara());
  if (PIN_KABEL >= 0 && digitalRead(PIN_KABEL) == HIGH) {         // sesuaikan logika rangkaian
    if (json.length() > 1) json += ",";
    json += "\"kabel_putus\":true";
  }
  json += "}";
  return json;
}

// ======== MQTT ========
WiFiClientSecure jaringanMqtt;
PubSubClient mqtt(jaringanMqtt);
String topikSensor;
String topikBalasan;

// Web membalas tiap pesan di ews/<id>/balasan; dicetak agar kelihatan kenapa data ditolak.
void saatBalasan(char* topik, byte* isi, unsigned int panjang) {
  Serial.print("BALASAN web: ");
  for (unsigned int i = 0; i < panjang; i++) Serial.print((char)isi[i]);
  Serial.println();
}

bool sambungMqtt() {
  if (mqtt.connected()) return true;
  String id = "ews-alat-" + String(WILAYAH_ID);   // harus unik per alat, atau dua alat saling menendang
  if (!mqtt.connect(id.c_str(), MQTT_USER, MQTT_PASS)) {
    Serial.printf("MQTT gagal tersambung, kode=%d\n", mqtt.state());
    return false;
  }
  mqtt.subscribe(topikBalasan.c_str());
  return true;
}

// Mengembalikan 201 bila pesan terkirim ke broker, nilai negatif bila gagal.
// Catatan: 201 hanya berarti broker menerima. Diterima atau tidaknya oleh web terlihat di BALASAN.
int kirimMqtt() {
  if (!sambungMqtt()) return -3;
  String json = rakitJson(false);
  bool ok = mqtt.publish(topikSensor.c_str(), json.c_str(), false);   // retain=false: wajib
  Serial.printf("MQTT %s %s -> %s\n", topikSensor.c_str(), json.c_str(), ok ? "terkirim" : "GAGAL");
  return ok ? 201 : -4;
}

// Mengembalikan kode HTTP (201 = sukses), atau nilai negatif jika gagal tersambung
int kirimHttp() {
  String json = rakitJson(true);

  WiFiClientSecure klien;
  klien.setInsecure();   // UJI COBA saja. Untuk pemasangan tetap, pakai klien.setCACert(<sertifikat root hosting>)
  HTTPClient http;
  http.setTimeout(15000);
  if (!http.begin(klien, URL_SENSOR)) return -1;
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  int kode = http.POST(json);
  Serial.printf("POST %s -> %d\n%s\n", json.c_str(), kode, kode > 0 ? http.getString().c_str() : "");
  http.end();
  return kode;
}

int kirimData() { return PAKAI_MQTT ? kirimMqtt() : kirimHttp(); }

// Memeriksa sertifikat butuh tanggal yang benar; ESP32 tidak punya jam sendiri setelah menyala.
void sinkronWaktu() {
  if (strlen(MQTT_CA_CERT) == 0 || time(nullptr) > 1700000000) return;
  configTime(0, 0, "pool.ntp.org", "time.google.com");
  unsigned long mulai = millis();
  while (time(nullptr) < 1700000000 && millis() - mulai < 15000) delay(250);
}

void sambungWifi() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long mulai = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - mulai < 20000) delay(500);
  if (WiFi.status() == WL_CONNECTED) sinkronWaktu();
}

// ======== KALIBRASI & PERINTAH ========
void cetakKalibrasi() {
  int tanah = bacaProbe(PIN_TANAH_AO, PIN_TANAH_DAYA);
  int hujan = bacaProbe(PIN_HUJAN_AO, PIN_HUJAN_DAYA);
  float miring = bacaKemiringan();
  float udara = bacaKelembapanUdara();
  Serial.printf("TANAH mentah=%d (%.1f%%) | HUJAN mentah=%d (%.1f%%) | MIRING=%.2f deg | UDARA=%.1f%%\n",
                tanah, keProsen(tanah, TANAH_KERING, TANAH_BASAH), hujan, keProsen(hujan, HUJAN_KERING, HUJAN_BASAH), miring, udara);
}

void periksaPerintahSerial() {
  if (!Serial.available()) return;
  String s = Serial.readStringUntil('\n');
  s.trim();
  if (s == "nol") simpanNol();                    // ketik "nol" di Serial Monitor
}

unsigned long tombolSejak = 0;
void periksaTombol() {
  if (PIN_TOMBOL_NOL < 0) return;
  if (digitalRead(PIN_TOMBOL_NOL) != LOW) { tombolSejak = 0; return; }
  if (tombolSejak == 0) tombolSejak = millis();
  if (millis() - tombolSejak >= 3000) {
    simpanNol();
    while (digitalRead(PIN_TOMBOL_NOL) == LOW) delay(50);        // tunggu dilepas agar tidak berulang
    tombolSejak = 0;
  }
}

void setup() {
  Serial.begin(115200);
  analogSetAttenuation(ADC_11db);                 // baca 0-3,3 V penuh
  if (PIN_TANAH_DAYA >= 0) { pinMode(PIN_TANAH_DAYA, OUTPUT); digitalWrite(PIN_TANAH_DAYA, LOW); }
  if (PIN_HUJAN_DAYA >= 0) { pinMode(PIN_HUJAN_DAYA, OUTPUT); digitalWrite(PIN_HUJAN_DAYA, LOW); }
  if (PIN_KABEL >= 0) pinMode(PIN_KABEL, INPUT_PULLUP);
  if (PIN_TOMBOL_NOL >= 0) pinMode(PIN_TOMBOL_NOL, INPUT_PULLUP);
  dht.begin();

  prefs.begin("ews", false);
  mpuSiap = mulaiMpu();
  Serial.println(mpuSiap ? "MPU-6050 terdeteksi" : "MPU-6050 TIDAK terdeteksi: cek kabel SDA/SCL/VCC/GND");
  nolAda = prefs.getBool("ada", false);
  if (nolAda) { nolX = prefs.getFloat("nx"); nolY = prefs.getFloat("ny"); nolZ = prefs.getFloat("nz"); }
  // Pertama kali menyala: anggap posisi sekarang adalah posisi pasang. Untuk mengulang, ketik "nol".
  if (mpuSiap && !nolAda) simpanNol();

  topikSensor = "ews/" + String(WILAYAH_ID) + "/sensor";
  topikBalasan = "ews/" + String(WILAYAH_ID) + "/balasan";
  if (strlen(MQTT_CA_CERT) > 0) jaringanMqtt.setCACert(MQTT_CA_CERT);
  else jaringanMqtt.setInsecure();                // UJI COBA saja: isi MQTT_CA_CERT untuk pemasangan tetap
  mqtt.setServer(MQTT_HOST, MQTT_PORT);
  mqtt.setBufferSize(512);                        // bawaan 256 byte, terlalu kecil bila isi bertambah
  mqtt.setKeepAlive(30);
  mqtt.setCallback(saatBalasan);

  if (!MODE_KALIBRASI) sambungWifi();
}

unsigned long terakhirKirim = 0;
int gagalBeruntun = 0;

void loop() {
  periksaPerintahSerial();
  periksaTombol();

  if (MODE_KALIBRASI) {
    cetakKalibrasi();
    delay(1000);
    return;
  }

  if (PAKAI_MQTT && WiFi.status() == WL_CONNECTED) mqtt.loop();   // jaga koneksi + terima BALASAN

  // Setelah gagal, tunggu makin lama (maks 5 menit) agar modem/baterai tidak terbebani
  unsigned long tunggu = INTERVAL_KIRIM_MS;
  if (gagalBeruntun > 0) tunggu = min(300000UL, 30000UL << min(gagalBeruntun - 1, 4));

  if (millis() - terakhirKirim >= tunggu || terakhirKirim == 0) {
    terakhirKirim = millis();
    sambungWifi();
    int kode = (WiFi.status() == WL_CONNECTED) ? kirimData() : -2;
    // 201 = sukses. 401/422 = kesalahan konfigurasi/format: mengulang tidak membantu, tapi jangan dikirim terlalu rapat.
    if (kode == 201) gagalBeruntun = 0; else gagalBeruntun++;
    if (gagalBeruntun >= 20) ESP.restart();        // modem/WiFi macet lama: mulai ulang
  }
  delay(100);
}
