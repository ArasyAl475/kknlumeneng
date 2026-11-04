// Contoh kode ESP32 -> website EWS Desa Lumeneng
// Koneksi: WiFi dari modem (MiFi/router 4G). Untuk modul GSM lihat catatan di PANDUAN_API_ESP32.md.
// Bagian bacaSensor*() adalah TEMPAT PENGISI: ganti dengan pembacaan sensor yang dipakai.
// Kalau sensor gagal dibaca, kembalikan NAN -> field tidak dikirim.

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <math.h>

// ======== PENGATURAN ========
const char* WIFI_SSID  = "NAMA_WIFI_MODEM";
const char* WIFI_PASS  = "PASSWORD_WIFI";
const char* URL_SENSOR = "https://DOMAIN-KAMU/api/sensor";
const char* API_KEY    = "ISI_SAMA_DENGAN_EWS_API_KEY";
const int   WILAYAH_ID = 1;                       // dari Admin -> Daftar wilayah (kolom ID alat)
const unsigned long INTERVAL_KIRIM_MS = 60000;    // kirim tiap 1 menit

// Tipping bucket: berapa mm air per satu ketukan
const float MM_PER_KETUKAN = 0.2;
const int   PIN_HUJAN = 27;
// Pin kabel EWS: LOW/HIGH sesuai rangkaian. Isi bila ada pendeteksi kabel putus.
const int   PIN_KABEL = -1;                       // -1 = tidak dipakai

// ======== CURAH HUJAN: ketukan dalam 1 jam terakhir ========
const int MAKS_KETUKAN = 600;                     // cukup untuk hujan sangat deras
volatile unsigned long waktuKetukan[MAKS_KETUKAN];
volatile int jumlahKetukan = 0;
volatile unsigned long terakhirIsr = 0;

void IRAM_ATTR isrHujan() {
  unsigned long now = millis();
  if (now - terakhirIsr < 50) return;             // debounce reed switch
  terakhirIsr = now;
  if (jumlahKetukan < MAKS_KETUKAN) waktuKetukan[jumlahKetukan++] = now;
}

float bacaCurahHujanMmPerJam() {
  const unsigned long SATU_JAM = 3600000UL;
  unsigned long now = millis();
  noInterrupts();
  int baru = 0;
  for (int i = 0; i < jumlahKetukan; i++)         // buang ketukan yang lebih tua dari 1 jam
    if (now - waktuKetukan[i] < SATU_JAM) waktuKetukan[baru++] = waktuKetukan[i];
  jumlahKetukan = baru;
  interrupts();
  return baru * MM_PER_KETUKAN;
}

// ======== SENSOR LAIN: ISI SESUAI PERANGKAT ========
float bacaKelembapanTanah() { return NAN; }       // 0-100 (%), hasil kalibrasi kering/basah
float bacaKemiringan()      { return NAN; }       // derajat, selisih dari posisi awal pemasangan
float bacaKelembapanUdara() { return NAN; }       // 0-100 (%), mis. dari DHT22/SHT31

// ======== KIRIM ========
// Menambahkan "nama":nilai ke JSON hanya jika sensornya terbaca
void tambah(String& json, const char* nama, float v) {
  if (isnan(v)) return;
  json += ",\"" + String(nama) + "\":" + String(v, 2);
}

// Mengembalikan kode HTTP (201 = sukses), atau nilai negatif jika gagal tersambung
int kirimData() {
  String json = "{\"wilayah_id\":" + String(WILAYAH_ID);
  tambah(json, "kelembapan_tanah", bacaKelembapanTanah());
  tambah(json, "curah_hujan", bacaCurahHujanMmPerJam());
  tambah(json, "kemiringan", bacaKemiringan());
  tambah(json, "kelembapan_udara", bacaKelembapanUdara());
  if (PIN_KABEL >= 0 && digitalRead(PIN_KABEL) == HIGH) json += ",\"kabel_putus\":true";   // sesuaikan logika rangkaian
  json += "}";

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

void sambungWifi() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long mulai = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - mulai < 20000) delay(500);
}

void setup() {
  Serial.begin(115200);
  pinMode(PIN_HUJAN, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(PIN_HUJAN), isrHujan, FALLING);
  if (PIN_KABEL >= 0) pinMode(PIN_KABEL, INPUT_PULLUP);
  sambungWifi();
}

unsigned long terakhirKirim = 0;
int gagalBeruntun = 0;

void loop() {
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
