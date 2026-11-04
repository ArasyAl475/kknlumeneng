<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\KirimTelegram;
use App\Models\Pengaturan;
use App\Models\SensorData;
use App\Models\Wilayah;
use App\Support\PenilaianStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EwsController extends Controller
{
    use KirimTelegram; // berisi sendTelegramNotification()

    // Dashboard publik
    public function index()
    {
        return view('dashboard', ['payload' => $this->bangunPayload()]);
    }

    // Form simulator (hanya admin)
    public function simulator()
    {
        return view('simulator', ['lokasi' => Wilayah::orderBy('id')->pluck('nama'), 'batas' => Pengaturan::ambang()]);
    }

    // Tes koneksi untuk alat: GET /api/ping (header X-API-KEY). Mengembalikan jam server dan daftar wilayah.
    public function ping(Request $request): JsonResponse
    {
        if ($galat = $this->periksaApiKey($request)) {
            return $galat;
        }

        return response()->json([
            'pesan'        => 'OK',
            'waktu_server' => now()->toIso8601String(),
            'wilayah'      => Wilayah::orderBy('id')->get(['id', 'nama']),
        ]);
    }

    // Menerima data dari simulator (web) DAN dari alat (API: POST /api/sensor)
    public function storeData(Request $request)
    {
        $dariApi = $request->is('api/*');

        // Alat harus membawa kunci rahasia. Form simulator dilindungi login admin.
        if ($dariApi && ($galat = $this->periksaApiKey($request))) {
            return $galat;
        }

        // Nama lama "kelembapan" tetap diterima sebagai kelembapan tanah
        if ($request->filled('kelembapan_tanah')) {
            $request->merge(['kelembapan' => $request->input('kelembapan_tanah')]);
        }

        $validator = Validator::make($request->all(), [
            'lokasi'           => ['required_without:wilayah_id', 'nullable', 'string', Rule::exists('wilayah', 'nama')],
            'wilayah_id'       => ['required_without:lokasi', 'nullable', 'integer', Rule::exists('wilayah', 'id')],
            'kelembapan'       => 'nullable|numeric|between:0,100',      // kelembapan tanah (%)
            'curah_hujan'      => 'nullable|numeric|between:0,1000',     // mm per jam
            'kemiringan'       => 'nullable|numeric|between:-90,90',     // derajat
            'kelembapan_udara' => 'nullable|numeric|between:0,100',      // %
            'kabel_putus'      => 'nullable|boolean',                    // alat mengirim 1 jika kabel EWS putus
        ]);

        $sensor = ['kelembapan', 'curah_hujan', 'kemiringan', 'kelembapan_udara'];
        $validator->after(function ($v) use ($request, $sensor) {
            $adaIsi = collect($sensor)->contains(fn ($k) => $request->filled($k));
            if (! $adaIsi && ! $request->boolean('kabel_putus')) {
                $v->errors()->add('kelembapan', 'Kirim minimal satu nilai sensor (kelembapan_tanah, curah_hujan, kemiringan, kelembapan_udara).');
            }
        });

        if ($validator->fails()) {
            return $dariApi
                ? response()->json(['pesan' => 'Data tidak valid', 'errors' => $validator->errors()], 422)
                : back()->withErrors($validator)->withInput();
        }

        $wilayah = $request->filled('wilayah_id')
            ? Wilayah::find($request->input('wilayah_id'))
            : Wilayah::where('nama', $request->input('lokasi'))->first();
        $lokasi  = $wilayah->nama;

        // Nilai kosong/null berarti sensor itu tidak mengirim (mis. rusak): disimpan sebagai kosong
        $angka = fn (string $k, int $desimal) => $request->filled($k) ? round((float) $request->input($k), $desimal) : null;
        $baca = [
            'kelembapan'       => $angka('kelembapan', 0) !== null ? (int) $angka('kelembapan', 0) : null,
            'curah_hujan'      => $angka('curah_hujan', 2),
            'kemiringan'       => $angka('kemiringan', 2),
            'kelembapan_udara' => $angka('kelembapan_udara', 2),
        ];

        $kabelPutus = $request->boolean('kabel_putus');
        $nilai      = PenilaianStatus::nilai($baca, Pengaturan::ambang(), $kabelPutus);
        $validasi   = $nilai['validasi'];

        // Alat asli mengirim data terus-menerus. Minta validasi admin SEKALI saja, bukan tiap kiriman.
        if ($validasi === 'menunggu' && $this->validasiSudahDiminta($lokasi, $wilayah)) {
            $validasi = null;
        }

        $data = SensorData::create($baca + [
            'lokasi'      => $lokasi,
            'status'      => $nilai['status'],
            'warna'       => $nilai['warna'],
            'pemicu'      => $nilai['pemicu'],
            'kabel_putus' => $kabelPutus,
            'validasi'    => $validasi,
        ]);

        $telegram = null;
        if ($kabelPutus) {
            // Status Awas "ditahan" sampai admin mencabutnya. Telegram dikirim sekali saat mulai Awas.
            if (! $wilayah->status_awas) {
                $wilayah->update(['status_awas' => true, 'awas_sumber' => 'Kabel EWS putus (otomatis)', 'awas_sejak' => now()]);
                $telegram = $this->sendTelegramNotification($lokasi, $baca['kelembapan'], 'awas_kabel', $data);
            }
        } elseif ($validasi === 'menunggu') {
            $telegram = $this->sendTelegramNotification($lokasi, $baca['kelembapan'], 'validasi', $data);
        }

        if ($dariApi) {
            return response()->json([
                'pesan'             => 'Data tersimpan',
                'data'              => $data,
                'menunggu_validasi' => $validasi === 'menunggu',
                'telegram_terkirim' => $telegram,
                'waktu_server'      => now()->toIso8601String(),
            ], 201);
        }

        $pesan = "Data tersimpan: {$lokasi} ({$nilai['status']}).";
        if ($nilai['pemicu'])         $pesan .= " Pemicu: {$nilai['pemicu']}.";
        if ($kabelPutus)              $pesan .= ' Wilayah otomatis AWAS.';
        if ($validasi === 'menunggu') $pesan .= ' Menunggu validasi admin.';
        if ($telegram === true)       $pesan .= ' Telegram terkirim.';
        if ($telegram === false)      $pesan .= ' Telegram TIDAK terkirim (cek .env, jeda anti-spam, atau storage/logs/laravel.log).';

        return redirect()->route('simulator')->with('sukses', $pesan);
    }

    // Dipanggil dashboard setiap 5 detik (AJAX polling)
    public function getLatestData()
    {
        return response()->json($this->bangunPayload());
    }

    private function periksaApiKey(Request $request): ?JsonResponse
    {
        $kunci = (string) config('ews.api_key');
        if ($kunci === '' || ! hash_equals($kunci, (string) $request->header('X-API-KEY'))) {
            return response()->json(['pesan' => 'API key salah atau belum diatur di .env'], 401);
        }

        return null;
    }

    // Sudah ada laporan menunggu, atau baru saja diputuskan admin, atau wilayah sudah Awas?
    private function validasiSudahDiminta(string $lokasi, Wilayah $wilayah): bool
    {
        if ($wilayah->status_awas) {
            return true;
        }

        $jeda = max(0, (int) config('ews.validasi_jeda_menit'));

        return SensorData::where('lokasi', $lokasi)
            ->where(function ($q) use ($jeda) {
                $q->where('validasi', 'menunggu')
                  ->orWhere(fn ($q2) => $q2->whereIn('validasi', ['ditolak', 'disetujui'])
                                           ->where('divalidasi_pada', '>=', now()->subMinutes($jeda)));
            })->exists();
    }

    private function bangunPayload(): array
    {
        $tingkat = ['hijau' => 1, 'kuning' => 2, 'merah' => 3];
        $ambang  = Pengaturan::ambang();
        $offline = (int) config('ews.alat_offline_menit');
        $lokasi  = [];
        $grafik  = [];
        $utama   = null; // status utama = kondisi TERBURUK dari semua wilayah

        foreach (Wilayah::orderBy('id')->get() as $w) {
            $terbaru = SensorData::where('lokasi', $w->nama)->latest('id')->first();
            $data    = $terbaru ? $this->format($terbaru, $ambang) : null;

            // Wilayah berstatus Awas tetap merah walau data terbaru terlihat normal
            if ($data && $w->status_awas) {
                $data['status'] = 'Awas';
                $data['warna']  = 'merah';
            }

            $umur = $terbaru ? max(0, (int) $terbaru->created_at->diffInMinutes(now(), true)) : null;

            $lokasi[] = [
                'id'        => $w->id,
                'lokasi'    => $w->nama,
                'poligon'   => $w->poligon,
                'awas'      => $w->status_awas ? $w->awas_sumber : null,
                'data'      => $data,
                'umur_menit' => $umur,
                'offline'   => $umur !== null && $offline > 0 && $umur >= $offline,
            ];

            // Riwayat untuk grafik: 60 bacaan terakhir per wilayah (lama -> baru)
            $grafik[] = [
                'lokasi' => $w->nama,
                'titik'  => SensorData::where('lokasi', $w->nama)->latest('id')->limit(60)->get()->reverse()->map(fn ($r) => [
                    't'      => $r->created_at->getTimestampMs(),
                    'tanah'  => $r->kelembapan,
                    'hujan'  => $r->curah_hujan,
                    'miring' => $r->kemiringan === null ? null : abs($r->kemiringan),
                    'udara'  => $r->kelembapan_udara,
                ])->values(),
            ];

            if ($data && ($utama === null || $tingkat[$data['warna']] > $tingkat[$utama['warna']])) {
                $utama = $data;
            }
        }

        return [
            'utama'      => $utama,
            'lokasi'     => $lokasi,
            'log'        => SensorData::latest('id')->limit(10)->get()->map(fn ($r) => $this->format($r, $ambang))->values(),
            'grafik'     => $grafik,
            'batas'      => $ambang,
            'offline_menit' => $offline,
            'diperbarui' => now()->format('H:i:s'),
        ];
    }

    private function format(SensorData $r, array $ambang): array
    {
        return [
            'lokasi'           => $r->lokasi,
            'kelembapan'       => $r->kelembapan,        // kelembapan tanah
            'curah_hujan'      => $r->curah_hujan,
            'kemiringan'       => $r->kemiringan,
            'kelembapan_udara' => $r->kelembapan_udara,
            'level'            => PenilaianStatus::levelSensor($r->only(['kelembapan', 'curah_hujan', 'kemiringan']), $ambang),
            'pemicu'           => $r->pemicu,
            'status'           => $r->status,
            'warna'            => $r->warna,
            'waktu'            => $r->created_at->format('d-m-Y H:i:s'),
            'jam'              => $r->created_at->format('H:i:s'),
        ];
    }
}
