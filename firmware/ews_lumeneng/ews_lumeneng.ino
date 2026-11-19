// =====================================================================================
//  Firmware EWS Longsor Desa Lumeneng  ->  https://desalumeneng.id
// =====================================================================================
//  Rangkaian:
//    Tanah  : HW-103 (LM393) + probe   VCC=GPIO25  AO=GPIO34  DO=GPIO35
//    Hujan  : modul hujan (LM393)      AO=GPIO36 (VP), VCC dari rel 3V3
//    Miring : MPU-6500 (GY-9250)       SDA=GPIO21  SCL=GPIO22  VCC=3V3
//    Udara  : DHT22                    out=GPIO23  +=3V3
//    Limit switch (kontak NC 11-12)    11=GPIO33   12=GPIO32
//    Relay 2 channel HW-383            IN1=GPIO27 (sekrup 5 COM / 6 NO -> sirene 12V)   IN2=GPIO26 (cadangan)
//
//  Cara kerja:
//    - Kirim data sensor ke POST /api/sensor (kontrak: docs/ews/API_ALAT.md) menyesuaikan keadaan:
//        Aman 30 menit sekali, Waspada/Awas 10 detik sekali, dan LANGSUNG bila keadaan berubah.
//    - Limit switch tergeser / kabelnya putus terus-menerus 3 detik -> AWAS:
//        sirene langsung bunyi selama SIRENE_DURASI_MS (tanpa menunggu internet), dan
//        "kabel_putus": true dikirim ke web sampai diterima. Wilayah di web otomatis AWAS.
//    - Sirene bunyi PENUH selama SIRENE_DURASI_MS, walau kiriman sesudahnya Aman atau Waspada.
//    - Alat TIDAK menyimpan status Awas. Yang menyimpan hanya web, sampai admin mencabutnya.
//      Jadi saat aki dipasang, sirene diam (kecuali limit switch memang sedang tergeser).
//    - Alat mengecek status web berkala:
//        web BERUBAH jadi Awas (admin menyetujui laporan / Nyatakan Awas) -> sirene bunyi
//        web BERUBAH dari Awas jadi tidak Awas (admin mencabut Awas)     -> sirene berhenti
//      Pengecekan pertama setelah menyala hanya mencatat status web dan tidak membunyikan sirene,
//      supaya Awas lama di web tidak membuat sirene bunyi tiap aki dipasang.
//    - Limit switch dan sirene diurus tugas terpisah, jadi tetap terpantau walau alat sedang
//      menunggu internet (kirim data bisa makan waktu sampai 15 detik saat sinyal lemah).
//
//  Library (Arduino IDE -> Tools -> Manage Libraries):
//    - "DHT sensor library" (Adafruit) + "Adafruit Unified Sensor"
//    - "ArduinoJson" (Benoit Blanchon) versi 7.x
//  Board: "ESP32 Dev Module" (paket board esp32 dari Espressif).
//
//  Nama WiFi, kata sandi WiFi, dan API_KEY ada di tab "rahasia.h" (tidak ikut Git).
//  Belum ada? Salin rahasia.contoh.h menjadi rahasia.h lalu isi.
//
//  Perintah di Serial Monitor (115200 baud, akhiri dengan Enter):
//    nol     simpan posisi sekarang sebagai kemiringan 0 derajat
//    diam    matikan sirene sekarang
//    reset   matikan sirene + batalkan kiriman Awas yang belum sampai ke web
//    sirene  tes sirene 3 detik
//    kirim   kirim data sekarang
//    kal     nyala/matikan cetak nilai mentah sensor tiap detik
//    status  cetak keadaan alat
// =====================================================================================

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Preferences.h>
#include <DHT.h>
#include <ArduinoJson.h>
#include <math.h>
#include <esp_system.h>

#include "rahasia.h"   // WIFI_SSID, WIFI_PASS, API_KEY

// ======================== PENGATURAN ========================
const char* SERVER = "https://desalumeneng.id";            // tanpa garis miring di akhir
// Angka "ID alat" di Admin -> Daftar wilayah. Saat menyala, alat mencetak daftar wilayah dari web.
const int WILAYAH_ID = 1;

// ---- Sirene ----
// Lama sirene bunyi per kejadian Awas.
// TES: 7 detik. Untuk lapangan ganti ke 10UL * 60UL * 1000UL (10 menit). 0 = bunyi terus sampai dicabut.
const unsigned long SIRENE_DURASI_MS = 7000UL;
const unsigned long TES_SIRENE_MS    = 3000UL;   // perintah "sirene" di Serial Monitor

// ---- Jadwal kirim menyesuaikan keadaan ----
// Sensor dibaca di alat tiap PERIKSA_SENSOR_MS. Bila TINGKAT berubah (Aman <-> Waspada <-> perlu
// validasi <-> Awas), data LANGSUNG dikirim. Bila tingkat sama, dikirim menurut interval di bawah.
// PENTING: web menandai alat "tidak mengirim data" setelah EWS_ALAT_OFFLINE_MENIT (bawaan 15 menit).
// Bila INTERVAL_AMAN_MS lebih dari itu, naikkan EWS_ALAT_OFFLINE_MENIT di .env server
// (mis. 45 untuk interval 30 menit, 70 untuk 1 jam).
const unsigned long INTERVAL_AMAN_MS    = 30UL * 60UL * 1000UL;   // Aman: 30 menit sekali
const unsigned long INTERVAL_WASPADA_MS = 10000;                  // Waspada: 10 detik sekali
const unsigned long INTERVAL_AWAS_MS    = 10000;                  // Awas: 10 detik sekali
const unsigned long PERIKSA_SENSOR_MS   = 5000;                   // baca sensor di alat tiap 5 detik
const unsigned long JEDA_MINIMAL_MS     = 5000;                   // dua kiriman paling rapat 5 detik
// Ambang cadangan bila belum berhasil mengambil dari web. Yang dipakai sebenarnya: ambang di
// Admin -> Ambang batas sensor (diambil otomatis bersama status web).
float ambangTanahW = 50, ambangTanahV = 80;
float ambangHujanW = 40, ambangHujanV = 70;
float ambangMiringW = 5,  ambangMiringV = 15;

// Alat mengikuti status AWAS di web (admin menyetujui / menyatakan / mencabut Awas).
// Tiap cek mengunduh data dashboard (±10-30 KB), jadi jangan terlalu sering supaya kuota modem awet.
const bool IKUTI_STATUS_WEB = true;
const unsigned long INTERVAL_CEK_STATUS_MS       = 180000;   // biasa: 3 menit
// Lebih sering saat sirene bunyi (agar pencabutan Awas cepat menghentikan sirene) dan saat status web
// belum pernah berhasil dibaca sejak menyala.
const unsigned long INTERVAL_CEK_STATUS_CEPAT_MS = 30000;    // 30 detik

// UNTUK TES: posisi kemiringan sekarang otomatis dianggap 0 derajat tiap ESP32 menyala.
// Ubah ke false saat alat dipasang permanen di lapangan (lalu pakai perintah "nol" sekali),
// supaya pergeseran tanah tidak "terhapus" ketika listrik mati lalu menyala lagi.
const bool NOL_OTOMATIS_SAAT_MENYALA = true;

// true = hanya cetak nilai mentah ke Serial Monitor, TIDAK mengirim ke web (untuk kalibrasi).
// Limit switch dan sirene tetap berfungsi supaya bisa dites.
const bool MODE_KALIBRASI = false;

// ======================== PIN ========================
// Pin AO harus ADC1 (GPIO 32-39) karena ADC2 tidak bisa dipakai saat WiFi menyala.
const int PIN_TANAH_AO   = 34;
const int PIN_TANAH_DO   = 35;   // hanya dicetak saat kalibrasi
const int PIN_TANAH_DAYA = 25;   // probe dialiri listrik hanya saat dibaca agar tidak cepat berkarat
const int PIN_HUJAN_AO   = 36;   // VP
const int PIN_HUJAN_DAYA = -1;   // -1 = VCC modul hujan langsung ke 3V3
const int PIN_DHT = 23;
const int PIN_SDA = 21;
const int PIN_SCL = 22;

// Limit switch dipasang di kontak NC: diam = tertutup, tergeser = terbuka.
// GPIO32 dijadikan "GND" (LOW), GPIO33 dibaca dengan pull-up:
//   diam -> LOW, tergeser ATAU kabelnya putus -> HIGH (aman: kabel putus juga dianggap bahaya).
const int  PIN_SAKLAR      = 33;
const int  PIN_SAKLAR_GND  = 32;
const bool SAKLAR_TERGESER_SAAT_HIGH = true;   // ubah ke false bila nanti dipindah ke kontak NO
const unsigned long LAMA_TERGESER_MS = 3000;   // harus tergeser terus 3 detik baru dianggap AWAS

const int  PIN_RELAY_SIRENE = 27;   // IN1. Di modul ini IN1 menyambung sekrup 4-6: hijau/LOAD+ di sekrup 5 (tengah), merah sirene di sekrup 6
const int  PIN_RELAY_2      = 26;   // IN2 (relay sekrup 1-3, belum dipakai, dibiarkan mati)
// Kebanyakan modul relay 2 channel aktif LOW (IN ditarik ke GND = relay nyala).
// Bila saat dites "sirene" relay tidak bunyi klik, ubah ke false.
const bool RELAY_AKTIF_LOW = true;

// ======================== KALIBRASI (ganti setelah mengukur) ========================
// Nilai mentah ADC 0-4095. Modul LM393: makin basah -> nilai makin KECIL.
const int TANAH_KERING = 4095;   // probe di udara (terukur 4095)
const int TANAH_BASAH  = 1400;   // probe di gelas air
const int HUJAN_KERING = 4095;   // papan kering (terukur 4095)
const int HUJAN_BASAH  = 1500;   // papan basah merata

// =====================================================================================

// Didefinisikan di atas semua fungsi: Arduino IDE menulis daftar fungsi otomatis di bagian atas file,
// dan daftar itu sudah menyebut Bacaan.
struct Bacaan { float tanah, hujan, miring, udara; };

DHT dht(PIN_DHT, DHT22);
Preferences prefs;

// ======================== KEADAAN BERSAMA (dua tugas) ========================
// Limit switch + sirene berjalan di tugasAlarm, internet di loop(). Variabel di bawah dibaca keduanya.
portMUX_TYPE kunciAlarm = portMUX_INITIALIZER_UNLOCKED;

// Hanya ditulis tugasAlarm.
volatile bool saklarAktif = false;          // limit switch sedang tergeser >= 3 detik
volatile uint32_t kejadianSaklar = 0;       // bertambah tiap limit switch tergeser >= 3 detik
// Hanya ditulis loop(). Pakai nomor kejadian (bukan satu bendera) supaya kejadian baru yang terjadi
// SAAT kiriman sedang berjalan tidak ikut dianggap terkirim.
volatile uint32_t kejadianTerkirim = 0;     // kejadian terakhir yang kabel_putus-nya diterima web
volatile bool kirimSegera = false;

// Diubah lewat bunyikanSirene()/hentikanSirene()/tesSirene() (dijaga kunciAlarm).
// Pin relay hanya disentuh tugasAlarm.
bool sireneAktif = false;
uint32_t sireneMulai = 0;
bool tesAktif = false;
uint32_t tesMulai = 0;
volatile bool sireneNyala = false;

// ---------- keadaan web (hanya loop()) ----------
bool statusWebDiketahui = false;   // sudah pernah berhasil membaca status web sejak menyala
bool webAwas = false;              // status wilayah di web = Awas
unsigned long awasTerkirimPada = 0;
String namaWilayah = "";           // nama wilayah di web untuk WILAYAH_ID (dari /api/ping)

bool cetakMentah = false;

bool awasBelumTerkirim() { return kejadianSaklar != kejadianTerkirim; }
bool kabelPutusDikirim() { return saklarAktif || awasBelumTerkirim(); }

// ======================== RELAY & SIRENE ========================
void aturRelay(int pin, bool nyala) {
  if (RELAY_AKTIF_LOW) {
    if (nyala) {
      pinMode(pin, OUTPUT);
      digitalWrite(pin, LOW);
    } else {
      // Dilepas (melayang), bukan HIGH: 3,3 V dari ESP32 sering tidak cukup mematikan relay 5 V.
      pinMode(pin, INPUT);
    }
  } else {
    pinMode(pin, OUTPUT);
    digitalWrite(pin, nyala ? HIGH : LOW);
  }
}

// Mulai (atau ulangi dari awal) satu kejadian sirene selama SIRENE_DURASI_MS.
void bunyikanSirene() {
  uint32_t sekarang = millis();
  portENTER_CRITICAL(&kunciAlarm);
  sireneAktif = true;
  sireneMulai = sekarang;
  portEXIT_CRITICAL(&kunciAlarm);
}

void hentikanSirene() {
  portENTER_CRITICAL(&kunciAlarm);
  sireneAktif = false;
  tesAktif = false;
  portEXIT_CRITICAL(&kunciAlarm);
}

void tesSirene() {
  uint32_t sekarang = millis();
  portENTER_CRITICAL(&kunciAlarm);
  tesAktif = true;
  tesMulai = sekarang;
  portEXIT_CRITICAL(&kunciAlarm);
}

// Hanya dipanggil tugasAlarm. Lama bunyi dihitung dari awal kejadian, tidak terpengaruh data sensor.
void perbaruiSirene() {
  uint32_t sekarang = millis();
  bool nyala;
  portENTER_CRITICAL(&kunciAlarm);
  if (sireneAktif && SIRENE_DURASI_MS > 0 && sekarang - sireneMulai >= SIRENE_DURASI_MS) sireneAktif = false;
  if (tesAktif && sekarang - tesMulai >= TES_SIRENE_MS) tesAktif = false;
  nyala = sireneAktif || tesAktif;
  portEXIT_CRITICAL(&kunciAlarm);

  if (nyala != sireneNyala) {
    sireneNyala = nyala;
    aturRelay(PIN_RELAY_SIRENE, nyala);
    Serial.println(nyala ? ">>> SIRENE NYALA" : ">>> sirene mati");
  }
}

// ======================== LIMIT SWITCH ========================
bool saklarTergeserSekarang() {
  bool high = digitalRead(PIN_SAKLAR) == HIGH;
  return SAKLAR_TERGESER_SAAT_HIGH ? high : !high;
}

// Hanya dipakai tugasAlarm.
uint32_t saklarSejak = 0;          // kapan mulai tergeser (0 = tidak tergeser)
uint32_t saklarNormalSejak = 0;

void periksaSaklar() {
  uint32_t sekarang = millis();
  if (saklarTergeserSekarang()) {
    saklarNormalSejak = 0;
    if (saklarSejak == 0) {
      saklarSejak = sekarang;
      Serial.println("Limit switch tergeser... menunggu 3 detik");
    }
    if (!saklarAktif && sekarang - saklarSejak >= LAMA_TERGESER_MS) {
      kejadianSaklar = kejadianSaklar + 1;   // hanya tugas ini yang menulis, jadi aman tanpa kunci
      saklarAktif = true;
      kirimSegera = true;
      bunyikanSirene();
      Serial.println("!!! LIMIT SWITCH TERGESER >= 3 DETIK -> AWAS, sirene bunyi !!!");
    }
  } else if (saklarSejak != 0) {
    // Harus normal 300 ms berturut-turut supaya pantulan kontak tidak mengulang hitungan 3 detik.
    if (saklarNormalSejak == 0) saklarNormalSejak = sekarang;
    if (sekarang - saklarNormalSejak >= 300) {
      if (saklarAktif) Serial.println("Limit switch kembali normal (Awas di web tetap sampai dicabut admin)");
      else Serial.println("Limit switch kembali normal sebelum 3 detik, diabaikan");
      saklarSejak = 0;
      saklarNormalSejak = 0;
      saklarAktif = false;
    }
  }
}

// Berjalan terus tiap 10 ms, terpisah dari loop(), jadi tidak ikut tertahan saat menunggu internet.
void tugasAlarm(void*) {
  for (;;) {
    periksaSaklar();
    perbaruiSirene();
    vTaskDelay(pdMS_TO_TICKS(10));
  }
}

// ======================== MPU-6500 / MPU-6050 ========================
uint8_t mpuAlamat = 0x68;
bool mpuSiap = false;
bool nolAda = false;
float nolX = 0, nolY = 0, nolZ = 0;

bool tulisMpu(uint8_t reg, uint8_t nilai) {
  Wire.beginTransmission(mpuAlamat);
  Wire.write(reg);
  Wire.write(nilai);
  return Wire.endTransmission() == 0;
}

int bacaRegMpu(uint8_t reg) {
  Wire.beginTransmission(mpuAlamat);
  Wire.write(reg);
  if (Wire.endTransmission(false) != 0) return -1;
  if (Wire.requestFrom((int)mpuAlamat, 1) != 1) return -1;
  return Wire.read();
}

bool mulaiMpu() {
  Wire.begin(PIN_SDA, PIN_SCL);
  const uint8_t kandidat[2] = {0x68, 0x69};   // AD0 kosong/GND = 0x68, AD0 ke VCC = 0x69
  for (int i = 0; i < 2; i++) {
    mpuAlamat = kandidat[i];
    if (tulisMpu(0x6B, 0x00)) {                // PWR_MGMT_1 = 0: bangunkan sensor
      delay(50);
      tulisMpu(0x1C, 0x00);                    // ACCEL_CONFIG: rentang +-2g
      int who = bacaRegMpu(0x75);              // WHO_AM_I
      const char* jenis = who == 0x70 ? "MPU-6500" : who == 0x68 ? "MPU-6050" :
                          who == 0x71 ? "MPU-9250" : who == 0x73 ? "MPU-9255" : "tidak dikenal";
      Serial.printf("Sensor kemiringan terdeteksi di 0x%02X, WHO_AM_I=0x%02X (%s)\n", mpuAlamat, who, jenis);
      return true;
    }
  }
  return false;
}

bool bacaAkselerasi(float& x, float& y, float& z) {
  Wire.beginTransmission(mpuAlamat);
  Wire.write(0x3B);                            // ACCEL_XOUT_H (sama di MPU-6050 dan MPU-6500)
  if (Wire.endTransmission(false) != 0) return false;
  if (Wire.requestFrom((int)mpuAlamat, 6) != 6) return false;
  int xh = Wire.read(), xl = Wire.read();
  int yh = Wire.read(), yl = Wire.read();
  int zh = Wire.read(), zl = Wire.read();
  x = (int16_t)((xh << 8) | xl) / 16384.0f;
  y = (int16_t)((yh << 8) | yl) / 16384.0f;
  z = (int16_t)((zh << 8) | zl) / 16384.0f;
  return true;
}

// Rata-rata 20 sampel supaya getaran sesaat tidak terbaca sebagai pergeseran.
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
  if (!mpuSiap || !bacaAkselerasiRata(x, y, z)) {
    Serial.println("Posisi nol GAGAL disimpan: sensor kemiringan tidak terbaca");
    return;
  }
  prefs.putFloat("nx", x); prefs.putFloat("ny", y); prefs.putFloat("nz", z); prefs.putBool("ada", true);
  nolX = x; nolY = y; nolZ = z; nolAda = true;
  Serial.println("Posisi nol kemiringan disimpan");
}

// Sudut antara arah gravitasi sekarang dan saat dipasang (derajat). Tidak melenceng seperti giroskop.
float bacaKemiringan() {
  float x, y, z;
  if (!mpuSiap || !nolAda || !bacaAkselerasiRata(x, y, z)) return NAN;
  float m1 = sqrtf(x * x + y * y + z * z);
  float m2 = sqrtf(nolX * nolX + nolY * nolY + nolZ * nolZ);
  if (m1 < 0.5f || m2 < 0.5f) return NAN;      // bukan ~1g: sensor/kabel bermasalah
  float c = (x * nolX + y * nolY + z * nolZ) / (m1 * m2);
  c = constrain(c, -1.0f, 1.0f);
  // Lebih dari 90 derajat = alat sudah terbalik/rebah. API hanya menerima -90..90, jadi dibatasi 90.
  float sudut = acosf(c) * 180.0f / (float)PI;
  return sudut > 90.0f ? 90.0f : sudut;
}

// ======================== TANAH, HUJAN, UDARA ========================
int bacaRata(int pin) {
  long jumlah = 0;
  for (int i = 0; i < 16; i++) { jumlah += analogRead(pin); delay(2); }
  return (int)(jumlah / 16);
}

int tanahDoTerakhir = -1;

int bacaProbe(int pinAo, int pinDaya, int pinDo) {
  if (pinDaya >= 0) { digitalWrite(pinDaya, HIGH); delay(150); }   // tunggu tegangan stabil
  int mentah = bacaRata(pinAo);
  if (pinDo >= 0) tanahDoTerakhir = digitalRead(pinDo);
  if (pinDaya >= 0) digitalWrite(pinDaya, LOW);
  return mentah;
}

float keProsen(int mentah, int kering, int basah) {
  if (mentah <= 0 || kering == basah) return NAN;                 // 0 = kabel salah/korslet
  float p = 100.0f * (kering - mentah) / (float)(kering - basah);
  return constrain(p, 0.0f, 100.0f);
}

int   bacaTanahMentah()     { return bacaProbe(PIN_TANAH_AO, PIN_TANAH_DAYA, PIN_TANAH_DO); }
int   bacaHujanMentah()     { return bacaProbe(PIN_HUJAN_AO, PIN_HUJAN_DAYA, -1); }
float bacaKelembapanTanah() { return keProsen(bacaTanahMentah(), TANAH_KERING, TANAH_BASAH); }
float bacaIntensitasHujan() { return keProsen(bacaHujanMentah(), HUJAN_KERING, HUJAN_BASAH); }

float bacaKelembapanUdara() {
  float h = dht.readHumidity();
  return (isnan(h) || h <= 0 || h > 100) ? NAN : h;   // tepat 0% = kabel data goyang, bukan bacaan
}

// ======================== WIFI ========================
unsigned long wifiCobaTerakhir = 0;
bool wifiSebelumnya = false;
int wifiGagal = 0;
volatile int wifiAlasan = -1;     // kode alasan putus/gagal terakhir dari driver WiFi

void wifiEvent(WiFiEvent_t e, WiFiEventInfo_t info) {
  if (e == ARDUINO_EVENT_WIFI_STA_DISCONNECTED) wifiAlasan = info.wifi_sta_disconnected.reason;
}

const char* teksAlasanWifi(int r) {
  switch (r) {
    case 201: return "nama WiFi TIDAK DITEMUKAN: modem mati/tidur, terlalu jauh, nama beda, atau hanya 5 GHz";
    case 2: case 15: case 202: case 204:
              return "PASSWORD SALAH atau ditolak modem";
    case 5: case 17:
              return "modem PENUH (terlalu banyak perangkat tersambung)";
    case 200: case 4: case 34:
              return "sinyal hilang / terlalu lemah";
    case 203: case 205:
              return "gagal tersambung (modem sibuk, coba nyalakan ulang modem)";
    default:  return "lainnya";
  }
}

// Mencari apakah nama WiFi terlihat oleh ESP32, beserta kekuatan sinyalnya.
void pindaiWifi() {
  int n = WiFi.scanNetworks();
  int sinyal = 0;
  bool ada = false;
  for (int i = 0; i < n; i++) {
    if (WiFi.SSID(i) == WIFI_SSID) { ada = true; sinyal = WiFi.RSSI(i); }
  }
  WiFi.scanDelete();
  if (ada) Serial.printf("   Pindai: \"%s\" TERLIHAT, sinyal %d dBm%s\n", WIFI_SSID, sinyal,
                         sinyal < -80 ? " (lemah, dekatkan modem)" : "");
  else     Serial.printf("   Pindai: \"%s\" TIDAK terlihat (%d WiFi lain terlihat). Cek modem menyala.\n", WIFI_SSID, n);
}

// Tidak menunggu (non-blocking) supaya alat tetap bekerja saat sinyal hilang.
void jagaWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    if (!wifiSebelumnya) {
      wifiSebelumnya = true;
      wifiGagal = 0;
      Serial.printf("WiFi tersambung ke \"%s\", IP %s, sinyal %d dBm\n",
                    WIFI_SSID, WiFi.localIP().toString().c_str(), WiFi.RSSI());
    }
    return;
  }
  if (wifiSebelumnya) { wifiSebelumnya = false; Serial.println("WiFi terputus"); }
  // Sambung ulang diatur sendiri tiap 15 detik (auto-reconnect bawaan dimatikan): bila keduanya jalan
  // bersamaan, driver WiFi bisa terus mengulang dan membanjiri Serial dengan "wifi:Set status to INIT".
  if (wifiCobaTerakhir != 0 && millis() - wifiCobaTerakhir < 15000) return;

  if (wifiCobaTerakhir != 0) {
    wifiGagal++;
    int r = wifiAlasan;
    if (r >= 0 && r != 8) Serial.printf("   Gagal WiFi (kode %d): %s\n", r, teksAlasanWifi(r));
  }
  wifiCobaTerakhir = millis();
  WiFi.disconnect();
  delay(100);
  if (wifiGagal > 0 && wifiGagal % 3 == 0) pindaiWifi();   // tiap 3 kali gagal, cek modem terlihat atau tidak
  wifiAlasan = -1;
  Serial.printf("Menyambung WiFi \"%s\"...\n", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
}

// ======================== HTTP ========================
String url(const char* jalur) { return String(SERVER) + jalur; }

// GET lalu langsung urai JSON dari aliran data (hemat RAM: hanya field dalam filter yang disimpan).
int getJson(const String& alamat, bool pakaiKunci, JsonDocument& filter, JsonDocument& hasil) {
  WiFiClientSecure klien;
  klien.setInsecure();   // tidak memeriksa sertifikat HTTPS (lihat catatan di bawah file)
  HTTPClient http;
  http.useHTTP10(true);  // tanpa "chunked" agar bisa diurai langsung dari aliran
  http.setTimeout(10000);
  http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
  if (!http.begin(klien, alamat)) return -1;
  http.addHeader("Accept", "application/json");
  if (pakaiKunci) http.addHeader("X-API-KEY", API_KEY);
  int kode = http.GET();
  if (kode == 200) {
    DeserializationError err = deserializeJson(hasil, http.getStream(), DeserializationOption::Filter(filter));
    if (err) { Serial.printf("Balasan bukan JSON yang benar: %s\n", err.c_str()); kode = -6; }
  }
  http.end();
  return kode;
}

// Cek kunci API + cari nama wilayah untuk WILAYAH_ID (dipakai mencocokkan status di dashboard).
bool ambilNamaWilayah() {
  JsonDocument filter;
  filter["wilayah"][0]["id"] = true;
  filter["wilayah"][0]["nama"] = true;
  JsonDocument doc;
  int kode = getJson(url("/api/ping"), true, filter, doc);
  if (kode != 200) {
    Serial.printf("PING %s -> %d %s\n", url("/api/ping").c_str(), kode,
                  kode == 401 ? "(API_KEY tidak sama dengan EWS_API_KEY di .env server)" : "");
    return false;
  }
  Serial.println("PING OK. Wilayah di web (ID alat - nama):");
  for (JsonObject w : doc["wilayah"].as<JsonArray>()) {
    int id = w["id"] | -1;
    const char* nama = w["nama"] | "";
    Serial.printf("   %d - %s%s\n", id, nama, id == WILAYAH_ID ? "   <== alat ini" : "");
    if (id == WILAYAH_ID) namaWilayah = nama;
  }
  if (namaWilayah.length() == 0) {
    Serial.printf("PERINGATAN: WILAYAH_ID=%d tidak ada di web. Data akan ditolak (422).\n", WILAYAH_ID);
    return false;
  }
  return true;
}

// Membaca status wilayah ini dari dashboard publik. Status Awas dari admin hanya ada di sini
// (balasan POST /api/sensor hanya berisi status bacaan itu sendiri, bukan Awas yang ditahan web).
void cekStatusWeb() {
  if (namaWilayah.length() == 0 && !ambilNamaWilayah()) return;
  JsonDocument filter;
  filter["wilayah"][0]["nama"] = true;
  filter["wilayah"][0]["status"] = true;
  filter["ambang"] = true;
  JsonDocument doc;
  int kode = getJson(url("/api/ews/data"), false, filter, doc);
  if (kode != 200) { Serial.printf("Cek status web gagal -> %d\n", kode); return; }

  // Ambang dari Admin -> Ambang batas sensor, supaya alat menilai perubahan dengan angka yang sama.
  JsonObject a = doc["ambang"].as<JsonObject>();
  if (!a.isNull()) {
    ambangTanahW  = a["kelembapan_tanah"]["waspada"]  | ambangTanahW;
    ambangTanahV  = a["kelembapan_tanah"]["validasi"] | ambangTanahV;
    ambangHujanW  = a["intensitas_hujan"]["waspada"]  | ambangHujanW;
    ambangHujanV  = a["intensitas_hujan"]["validasi"] | ambangHujanV;
    ambangMiringW = a["kemiringan"]["waspada"]        | ambangMiringW;
    ambangMiringV = a["kemiringan"]["validasi"]       | ambangMiringV;
  }

  bool ketemu = false;
  String status = "";
  for (JsonObject w : doc["wilayah"].as<JsonArray>()) {
    if (namaWilayah == (const char*)(w["nama"] | "")) {
      ketemu = true;
      status = (const char*)(w["status"] | "belum ada data");
    }
  }
  if (!ketemu) {                     // nama wilayah diganti admin: ambil ulang lain kali
    Serial.println("Wilayah tidak ditemukan di dashboard, nama akan dicek ulang");
    namaWilayah = "";
    return;
  }

  bool awas = status == "Awas";
  Serial.printf("Status web \"%s\": %s\n", namaWilayah.c_str(), status.c_str());

  // Pengecekan pertama sejak menyala hanya dicatat: Awas lama yang belum dicabut admin tidak boleh
  // membunyikan sirene tiap kali aki dipasang. Sirene hanya untuk kejadian BARU.
  if (!statusWebDiketahui) {
    statusWebDiketahui = true;
    webAwas = awas;
    if (awas) Serial.println("   Web masih Awas dari sebelumnya: sirene TIDAK dibunyikan. Cabut lewat Admin bila sudah aman.");
    return;
  }

  if (awas && !webAwas) {
    webAwas = true;
    Serial.println("Web BARU menyatakan AWAS (admin menyetujui / menyatakan Awas) -> sirene bunyi");
    bunyikanSirene();
  } else if (!awas && webAwas) {
    // Jangan anggap dicabut bila alat masih/baru saja mengirim kabel_putus: data dashboard bisa
    // tertinggal beberapa detik, dan web akan langsung Awas lagi selama limit switch tergeser.
    bool baruTerkirim = awasTerkirimPada != 0 && millis() - awasTerkirimPada < 20000;
    if (saklarAktif || awasBelumTerkirim() || baruTerkirim) return;
    webAwas = false;
    hentikanSirene();
    Serial.println("Awas dicabut admin di web -> sirene dihentikan, alat kembali normal");
  }
}

void tambahSensor(JsonDocument& doc, const char* nama, float v) {
  if (!isnan(v)) doc[nama] = serialized(String(v, 2));   // sensor gagal = field tidak dikirim (bukan 0)
}

// ======================== PENILAIAN DI ALAT ========================
// Hanya untuk menentukan KAPAN mengirim. Status resmi tetap dihitung web (PenilaianStatus).
Bacaan bacaanTerakhir = {NAN, NAN, NAN, NAN};
int tingkatSekarang = -1;   // hasil baca sensor terbaru di alat
int tingkatTerkirim = -1;   // tingkat pada kiriman terakhir yang berhasil (-1 = belum ada)

Bacaan bacaSemua() {
  Bacaan b;
  b.tanah  = bacaKelembapanTanah();
  b.hujan  = bacaIntensitasHujan();
  b.miring = bacaKemiringan();
  b.udara  = bacaKelembapanUdara();
  return b;
}

// Histeresis: naik level begitu melewati batas, tapi TURUN level hanya bila nilainya sudah di bawah
// batas dikurangi selisih ini. Tanpa ini, nilai yang naik-turun tipis di sekitar batas (mis. 4,9 / 5,1
// derajat) membuat alat mengirim "perubahan" terus-menerus.
const float HISTERESIS_PERSEN  = 3.0f;   // tanah dan hujan
const float HISTERESIS_DERAJAT = 1.0f;   // kemiringan
int lvTanah = 0, lvHujan = 0, lvMiring = 0;

int levelSensor(float v, float waspada, float validasi, float selisih, int& lvLama) {
  if (isnan(v)) { lvLama = 0; return 0; }
  int lv = v >= validasi ? 2 : (v >= waspada ? 1 : 0);
  if (lv < lvLama && v > (lvLama == 2 ? validasi : waspada) - selisih) lv = lvLama;
  lvLama = lv;
  return lv;
}

// 0 = Aman, 1 = Waspada, 2 = Waspada + minta validasi admin, 3 = Awas (limit switch)
int hitungTingkat(const Bacaan& b) {
  if (kabelPutusDikirim()) return 3;
  int t = levelSensor(b.tanah, ambangTanahW, ambangTanahV, HISTERESIS_PERSEN, lvTanah);
  t = max(t, levelSensor(b.hujan, ambangHujanW, ambangHujanV, HISTERESIS_PERSEN, lvHujan));
  t = max(t, levelSensor(isnan(b.miring) ? NAN : fabsf(b.miring), ambangMiringW, ambangMiringV, HISTERESIS_DERAJAT, lvMiring));
  return t;
}

const char* namaTingkat(int t) {
  switch (t) {
    case 0: return "Aman";
    case 1: return "Waspada";
    case 2: return "Waspada (perlu validasi)";
    case 3: return "AWAS";
    default: return "belum diketahui";
  }
}

unsigned long intervalTingkat(int t) {
  if (t >= 3) return INTERVAL_AWAS_MS;
  if (t >= 1) return INTERVAL_WASPADA_MS;
  return INTERVAL_AMAN_MS;
}

// Mengembalikan kode HTTP (201 = tersimpan) atau angka negatif bila gagal.
int kirimData(const Bacaan& b) {
  uint32_t kejadian = kejadianSaklar;   // dicatat sebelum kirim; kejadian baru selama kirim tetap menunggu
  bool kabelPutus = saklarAktif || kejadian != kejadianTerkirim;

  JsonDocument doc;
  doc["wilayah_id"] = WILAYAH_ID;
  tambahSensor(doc, "kelembapan_tanah", b.tanah);
  tambahSensor(doc, "intensitas_hujan", b.hujan);
  tambahSensor(doc, "kemiringan", b.miring);
  tambahSensor(doc, "kelembapan_udara", b.udara);
  if (kabelPutus) doc["kabel_putus"] = true;
  if (doc.size() <= 1) { Serial.println("Semua sensor gagal dibaca, tidak ada yang dikirim"); return -5; }

  String json;
  serializeJson(doc, json);

  WiFiClientSecure klien;
  klien.setInsecure();
  HTTPClient http;
  http.setTimeout(15000);
  if (!http.begin(klien, url("/api/sensor"))) return -1;
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  int kode = http.POST(json);
  String balasan = kode > 0 ? http.getString() : "";
  http.end();

  Serial.printf("POST %s -> %d\n", json.c_str(), kode);
  if (kode == 201) {
    JsonDocument b;
    if (!deserializeJson(b, balasan)) {
      Serial.printf("   tersimpan, status bacaan: %s%s\n", (const char*)(b["data"]["status"] | "?"),
                    (b["menunggu_validasi"] | false) ? " (menunggu validasi admin)" : "");
    }
    if (kabelPutus) {
      bool kejadianBaru = kejadianTerkirim != kejadian;
      kejadianTerkirim = kejadian;
      awasTerkirimPada = millis();
      webAwas = true;                // web pasti Awas karena kabel_putus diterima
      if (kejadianBaru) Serial.println("   AWAS diterima web (web menahan Awas sampai admin mencabutnya)");
    }
  } else if (kode == 401) {
    Serial.println("   401: API_KEY tidak sama dengan EWS_API_KEY di .env server");
  } else if (kode == 422) {
    Serial.printf("   422: data ditolak -> %s\n", balasan.c_str());
  } else if (kode == 429) {
    Serial.println("   429: terlalu sering mengirim");
  } else if (kode < 0) {
    Serial.printf("   gagal tersambung ke server (%s)\n", http.errorToString(kode).c_str());
  } else {
    Serial.printf("   balasan server: %s\n", balasan.substring(0, 200).c_str());
  }
  return kode;
}

// ======================== SERIAL ========================
void cetakMentahSensor() {
  int tanah = bacaTanahMentah();
  int hujan = bacaHujanMentah();
  float miring = bacaKemiringan();
  float udara = bacaKelembapanUdara();
  float suhu = dht.readTemperature();
  Serial.printf("TANAH mentah=%d (%.1f%%) DO=%d | HUJAN mentah=%d (%.1f%%) | MIRING=%.2f deg | UDARA=%.1f%% %.1fC | SAKLAR=%s\n",
                tanah, keProsen(tanah, TANAH_KERING, TANAH_BASAH), tanahDoTerakhir,
                hujan, keProsen(hujan, HUJAN_KERING, HUJAN_BASAH), miring, udara, suhu,
                saklarTergeserSekarang() ? "TERGESER" : "normal");
}

void cetakStatus() {
  Serial.println("----- STATUS ALAT -----");
  Serial.printf("WiFi        : %s\n", WiFi.status() == WL_CONNECTED ? "tersambung" : "TIDAK tersambung");
  Serial.printf("Wilayah     : ID %d %s\n", WILAYAH_ID, namaWilayah.c_str());
  Serial.printf("Limit switch: %s%s\n", saklarTergeserSekarang() ? "tergeser" : "normal", saklarAktif ? " (AKTIF)" : "");
  Serial.printf("Kirim Awas  : %s\n", awasBelumTerkirim() ? "MENUNGGU terkirim ke web" : "tidak ada yang menunggu");
  Serial.printf("Status web  : %s\n", !statusWebDiketahui ? "belum diketahui" : (webAwas ? "AWAS" : "tidak Awas"));
  Serial.printf("Keadaan alat: %s (terkirim: %s), interval kirim %lu detik\n", namaTingkat(tingkatSekarang),
                namaTingkat(tingkatTerkirim), intervalTingkat(tingkatSekarang) / 1000UL);
  Serial.printf("Ambang      : tanah %.0f/%.0f%%, hujan %.0f/%.0f%%, miring %.1f/%.1f deg (waspada/validasi)\n",
                ambangTanahW, ambangTanahV, ambangHujanW, ambangHujanV, ambangMiringW, ambangMiringV);
  Serial.printf("Sirene      : %s (lama bunyi per kejadian %lu detik)\n", sireneNyala ? "NYALA" : "mati",
                SIRENE_DURASI_MS / 1000UL);
  Serial.printf("Kemiringan  : %s\n", mpuSiap ? (nolAda ? "siap" : "posisi nol belum disimpan") : "sensor tidak terdeteksi");
}

void periksaPerintahSerial() {
  if (!Serial.available()) return;
  String s = Serial.readStringUntil('\n');
  s.trim();
  s.toLowerCase();
  if (s == "nol") simpanNol();
  else if (s == "diam") { hentikanSirene(); Serial.println("Sirene dimatikan"); }
  else if (s == "reset") {
    kejadianTerkirim = kejadianSaklar;
    hentikanSirene();
    Serial.println("Sirene dimatikan dan kiriman Awas yang belum terkirim dibatalkan.");
    Serial.println("Awas yang sudah ada di web hanya bisa dicabut lewat Admin.");
  }
  else if (s == "sirene") { tesSirene(); Serial.println("Tes sirene 3 detik"); }
  else if (s == "kirim") kirimSegera = true;
  else if (s == "kal") { cetakMentah = !cetakMentah; Serial.println(cetakMentah ? "Cetak nilai mentah: NYALA" : "Cetak nilai mentah: mati"); }
  else if (s == "status") cetakStatus();
  else if (s.length() > 0) Serial.println("Perintah: nol, diam, reset, sirene, kirim, kal, status");
}

// ======================== SETUP & LOOP ========================
// Membantu mencari tahu kenapa ESP32 tiba-tiba menyala ulang.
void cetakPenyebabRestart(esp_reset_reason_t r) {
  const char* teks;
  switch (r) {
    case ESP_RST_POWERON:  teks = "listrik baru dinyalakan"; break;
    case ESP_RST_EXT:
    case ESP_RST_SW:       teks = "tombol reset / upload / perintah restart"; break;
    case ESP_RST_PANIC:    teks = "PROGRAM ERROR (crash)"; break;
    case ESP_RST_INT_WDT:
    case ESP_RST_TASK_WDT:
    case ESP_RST_WDT:      teks = "WATCHDOG (program macet)"; break;
    case ESP_RST_BROWNOUT: teks = "TEGANGAN TURUN (brownout): catu daya kurang kuat"; break;
    default:               teks = "lainnya"; break;
  }
  Serial.printf("Penyebab menyala: %s (kode %d)\n", teks, (int)r);
}

void setup() {
  // Relay dimatikan paling awal supaya sirene tidak bunyi sesaat ketika ESP32 menyala.
  aturRelay(PIN_RELAY_SIRENE, false);
  aturRelay(PIN_RELAY_2, false);

  Serial.begin(115200);
  delay(300);
  Serial.println("\n=== EWS Longsor Desa Lumeneng ===");
  esp_reset_reason_t penyebab = esp_reset_reason();
  cetakPenyebabRestart(penyebab);

  if (PIN_TANAH_DAYA >= 0) { pinMode(PIN_TANAH_DAYA, OUTPUT); digitalWrite(PIN_TANAH_DAYA, LOW); }
  if (PIN_HUJAN_DAYA >= 0) { pinMode(PIN_HUJAN_DAYA, OUTPUT); digitalWrite(PIN_HUJAN_DAYA, LOW); }
  pinMode(PIN_TANAH_DO, INPUT);
  pinMode(PIN_SAKLAR_GND, OUTPUT);
  digitalWrite(PIN_SAKLAR_GND, LOW);
  pinMode(PIN_SAKLAR, INPUT_PULLUP);
  dht.begin();

  prefs.begin("ews", false);
  // Firmware lama menyimpan status Awas di memori alat, sehingga sirene bunyi tiap aki dipasang.
  // Sisa simpanan itu dihapus; status Awas sekarang hanya disimpan web.
  if (prefs.isKey("alarm")) prefs.remove("alarm");
  if (prefs.isKey("blmkirim")) prefs.remove("blmkirim");
  // Satu pengecualian: bila alat memulai ulang DIRINYA SENDIRI (lama gagal kirim) saat ada Awas yang
  // belum sampai ke web, Awas itu tetap dikirim (tanpa membunyikan sirene lagi). Pasang aki / tekan
  // tombol reset tidak termasuk, jadi tetap mulai bersih.
  bool titipan = prefs.isKey("titip") && prefs.getBool("titip", false);
  if (prefs.isKey("titip")) prefs.remove("titip");
  if (titipan && penyebab == ESP_RST_SW) {
    kejadianSaklar = 1;
    Serial.println("Awas yang belum sempat terkirim sebelum alat memulai ulang akan dikirim ke web");
  }

  // Limit switch + sirene dijalankan sedini mungkin, terpisah dari urusan internet.
  // Prioritas 2 (di atas loop()) supaya tetap jalan walau loop() sedang menunggu server.
  xTaskCreatePinnedToCore(tugasAlarm, "alarm", 4096, nullptr, 2, nullptr, 1);

  mpuSiap = mulaiMpu();
  if (!mpuSiap) Serial.println("Sensor kemiringan TIDAK terdeteksi: cek kabel SDA(21)/SCL(22)/VCC/GND");
  nolAda = prefs.getBool("ada", false);
  if (nolAda) { nolX = prefs.getFloat("nx"); nolY = prefs.getFloat("ny"); nolZ = prefs.getFloat("nz"); }
  // Pertama kali menyala: posisi sekarang dianggap posisi pasang. Ulangi dengan perintah "nol".
  if (mpuSiap && (!nolAda || NOL_OTOMATIS_SAAT_MENYALA)) simpanNol();

  if (MODE_KALIBRASI) {
    Serial.println("MODE KALIBRASI: data TIDAK dikirim ke web. Nilai mentah dicetak tiap detik.");
    cetakMentah = true;
  } else {
    WiFi.persistent(false);         // jangan tulis ulang flash tiap menyambung
    WiFi.onEvent(wifiEvent);
    WiFi.mode(WIFI_STA);
    WiFi.setSleep(false);           // mode hemat daya WiFi sering membuat modem MiFi memutus ESP32
    WiFi.setAutoReconnect(false);
    jagaWifi();
  }
  Serial.printf("Sirene bunyi %lu detik per kejadian Awas. Ketik \"status\" untuk melihat keadaan alat.\n",
                SIRENE_DURASI_MS / 1000UL);
}

unsigned long terakhirKirim = 0;
unsigned long terakhirSukses = 0;
unsigned long terakhirPeriksa = 0;
unsigned long terakhirCekStatus = 0;
unsigned long terakhirCetak = 0;
bool sudahPing = false;
int gagalBeruntun = 0;

// Bila tidak ada kiriman yang berhasil selama ini, ESP32 dimulai ulang (modem/WiFi macet).
const unsigned long BATAS_MACET_MS = max(45UL * 60UL * 1000UL, INTERVAL_AMAN_MS + 15UL * 60UL * 1000UL);

void loop() {
  periksaPerintahSerial();

  if (cetakMentah && millis() - terakhirCetak >= 1000) {
    terakhirCetak = millis();
    cetakMentahSensor();
  }

  if (MODE_KALIBRASI) { delay(20); return; }

  jagaWifi();
  bool online = WiFi.status() == WL_CONNECTED;

  if (online && !sudahPing) { sudahPing = true; ambilNamaWilayah(); }

  // Status + ambang dari web diambil dulu (juga saat baru menyala) supaya penilaian di alat sama dengan web.
  unsigned long intervalCek = (!statusWebDiketahui || sireneNyala) ? INTERVAL_CEK_STATUS_CEPAT_MS : INTERVAL_CEK_STATUS_MS;
  if (IKUTI_STATUS_WEB && online && (terakhirCekStatus == 0 || millis() - terakhirCekStatus >= intervalCek)) {
    terakhirCekStatus = millis();
    cekStatusWeb();
  }

  // 1) Baca sensor di alat secara berkala dan tentukan tingkat keadaannya.
  if (kirimSegera || terakhirPeriksa == 0 || millis() - terakhirPeriksa >= PERIKSA_SENSOR_MS) {
    terakhirPeriksa = millis();
    bacaanTerakhir = bacaSemua();
    int t = hitungTingkat(bacaanTerakhir);
    if (tingkatSekarang != -1 && t != tingkatSekarang) {
      Serial.printf("PERUBAHAN keadaan: %s -> %s, data langsung dikirim\n", namaTingkat(tingkatSekarang), namaTingkat(t));
    }
    tingkatSekarang = t;
  }

  // 2) Putuskan kapan mengirim.
  //    - tingkat berubah dibanding kiriman terakhir yang berhasil -> langsung (paling rapat JEDA_MINIMAL_MS)
  //    - tingkat sama -> sesuai interval tingkatnya (Aman jarang, Waspada/Awas sering)
  //    - setelah gagal -> dicoba lagi makin jarang (15 dtk .. 5 mnt), tapi tidak lebih jarang dari interval tingkat
  bool berubah = tingkatSekarang != tingkatTerkirim;
  unsigned long tunggu = intervalTingkat(tingkatSekarang);
  if (gagalBeruntun > 0) {
    unsigned long mundur = min(300000UL, 30000UL << min(gagalBeruntun - 1, 4));
    tunggu = max(15000UL, min(tunggu, mundur));
  }
  unsigned long sejak = millis() - terakhirKirim;
  bool kirim;
  if (terakhirKirim == 0 || kirimSegera) kirim = true;
  else if (berubah) kirim = gagalBeruntun == 0 ? sejak >= JEDA_MINIMAL_MS : sejak >= tunggu;
  else kirim = sejak >= tunggu;

  if (kirim) {
    kirimSegera = false;
    terakhirKirim = millis();
    int tingkatDikirim = tingkatSekarang;
    int kode = online ? kirimData(bacaanTerakhir) : -2;
    if (!online) Serial.println("Belum tersambung WiFi, data tidak terkirim");

    if (kode == 201 || kode == -5) {          // -5 = semua sensor gagal dibaca, tidak ada yang dikirim
      gagalBeruntun = 0;
      tingkatTerkirim = tingkatDikirim;
      if (kode == 201) terakhirSukses = millis();
      Serial.printf("   kiriman berikutnya: %lu detik lagi (kecuali keadaan berubah)\n",
                    intervalTingkat(tingkatDikirim) / 1000UL);
    } else {
      gagalBeruntun++;
    }
    // AWAS yang baru diterima web mengubah tingkat (bila limit switch sudah normal) -> dinilai ulang.
    tingkatSekarang = hitungTingkat(bacaanTerakhir);

    if (gagalBeruntun > 0 && millis() - terakhirSukses > BATAS_MACET_MS) {
      Serial.println("Lama tidak berhasil mengirim, ESP32 dimulai ulang...");
      if (awasBelumTerkirim()) prefs.putBool("titip", true);   // lihat penjelasan di setup()
      delay(500);
      ESP.restart();
    }
  }

  delay(20);
}

// Catatan HTTPS: setInsecure() berarti data tetap terenkripsi, tetapi alat tidak memeriksa
// keaslian sertifikat server. Untuk keamanan lebih, ganti dengan klien.setCACert(<sertifikat root>)
// sesuai penerbit SSL desalumeneng.id (lihat ikon gembok di browser), lalu sinkronkan jam lewat NTP.
