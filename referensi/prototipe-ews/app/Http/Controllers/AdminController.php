<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\KirimTelegram;
use App\Models\Pengaturan;
use App\Models\SensorData;
use App\Models\Wilayah;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use KirimTelegram;

    public function dashboard()
    {
        $wilayah = Wilayah::orderBy('id')->get();

        return view('admin.dashboard', [
            'menunggu'    => SensorData::where('validasi', 'menunggu')->latest('id')->limit(30)->get(),
            'wilayah'     => $wilayah,
            'ambang'      => Pengaturan::ambang(),
            'terakhir'    => $wilayah->mapWithKeys(fn ($w) => [$w->nama => SensorData::where('lokasi', $w->nama)->latest('id')->first()]),
            'offlineMenit' => (int) config('ews.alat_offline_menit'),
            'wilayahPeta' => $wilayah->map(fn ($w) => ['nama' => $w->nama, 'poligon' => $w->poligon, 'awas' => $w->status_awas])->values(),
        ]);
    }

    // Admin memutuskan laporan yang nilai sensornya melewati batas validasi: setujui (jadi AWAS) atau tolak
    public function validasi(Request $request, SensorData $sensorData)
    {
        $keputusan = $request->validate(['keputusan' => 'required|in:setujui,tolak'])['keputusan'];

        if ($sensorData->validasi !== 'menunggu') {
            return back()->withErrors(['validasi' => 'Laporan ini sudah divalidasi sebelumnya.']);
        }

        if ($keputusan === 'tolak') {
            $sensorData->update(['validasi' => 'ditolak', 'divalidasi_pada' => now()]);
            return back()->with('sukses', 'Laporan ditolak. Status wilayah tidak berubah.');
        }

        $sensorData->update(['validasi' => 'disetujui', 'divalidasi_pada' => now(), 'status' => 'Awas', 'warna' => 'merah']);

        $pesan   = "Laporan disetujui. {$sensorData->lokasi} sekarang berstatus AWAS.";
        $wilayah = Wilayah::where('nama', $sensorData->lokasi)->first();
        if ($wilayah && ! $wilayah->status_awas) {
            $wilayah->update(['status_awas' => true, 'awas_sumber' => 'Dinyatakan admin setelah validasi', 'awas_sejak' => now()]);
            $pesan .= $this->sendTelegramNotification($sensorData->lokasi, $sensorData->kelembapan, 'awas_validasi', $sensorData)
                ? ' Telegram terkirim.' : ' Telegram tidak terkirim (cek .env).';
        }

        return back()->with('sukses', $pesan);
    }

    // Admin mencabut status Awas setelah situasi aman
    public function cabutAwas(Wilayah $wilayah)
    {
        $wilayah->update(['status_awas' => false, 'awas_sumber' => null, 'awas_sejak' => null]);

        return back()->with('sukses', "Status Awas {$wilayah->nama} dicabut. Status kembali mengikuti data sensor.");
    }

    public function simpanAmbang(Request $request)
    {
        $d = $request->validate([
            'batas_waspada'         => 'required|integer|between:1,99',
            'batas_validasi'        => 'required|integer|gt:batas_waspada|max:100',
            'batas_hujan_waspada'   => 'required|numeric|min:0.1|max:1000',
            'batas_hujan_validasi'  => 'required|numeric|gt:batas_hujan_waspada|max:1000',
            'batas_miring_waspada'  => 'required|numeric|min:0.1|max:90',
            'batas_miring_validasi' => 'required|numeric|gt:batas_miring_waspada|max:90',
        ], [
            'batas_validasi.gt'        => 'Batas validasi kelembapan tanah harus lebih besar dari batas waspada.',
            'batas_hujan_validasi.gt'  => 'Batas validasi curah hujan harus lebih besar dari batas waspada.',
            'batas_miring_validasi.gt' => 'Batas validasi kemiringan harus lebih besar dari batas waspada.',
        ]);

        foreach ($d as $kunci => $nilai) {
            Pengaturan::simpan($kunci, $nilai);
        }

        return back()->with('sukses', 'Ambang batas disimpan.');
    }

    // Menambah wilayah dari bentuk yang digambar di peta
    public function tambahWilayah(Request $request)
    {
        $request->validate([
            'nama'    => 'required|string|max:100|unique:wilayah,nama',
            'poligon' => 'required|json',
        ], ['poligon.required' => 'Gambar dulu bentuk daerahnya di peta.', 'nama.unique' => 'Nama daerah sudah dipakai.']);

        $gagal = fn ($pesan) => back()->withErrors(['poligon' => $pesan])->withInput();

        $titik = json_decode($request->input('poligon'), true);
        if (! is_array($titik)) {
            return $gagal('Format batas daerah tidak valid.');
        }
        foreach ($titik as $t) {
            if (! is_array($t) || count($t) !== 2 || ! is_numeric($t[0]) || ! is_numeric($t[1]) || abs($t[0]) > 90 || abs($t[1]) > 180) {
                return $gagal('Ada titik dengan koordinat tidak valid.');
            }
        }

        $titik = array_map(fn ($t) => [round((float) $t[0], 6), round((float) $t[1], 6)], array_values($titik));
        if (count($titik) > 1 && $titik[0] === end($titik)) {
            array_pop($titik); // buang titik penutup yang dobel
        }

        // ===== ATURAN: harus berupa BANGUN, bukan garis =====
        if (count(array_unique(array_map('json_encode', $titik))) < 3) {
            return $gagal('Daerah harus berbentuk bangun (minimal 3 titik berbeda), bukan garis.');
        }
        if ($this->luas($titik) < 1e-9) {
            return $gagal('Titik-titiknya sejajar sehingga membentuk garis, bukan bangun.');
        }
        if ($this->berpotongan($titik)) {
            return $gagal('Sisi-sisi bangun saling bersilangan. Gambar ulang tanpa menyilang.');
        }

        Wilayah::create(['nama' => $request->input('nama'), 'poligon' => $titik]);

        return redirect()->route('admin.dashboard')->with('sukses', 'Daerah "' . $request->input('nama') . '" ditambahkan.');
    }

    public function hapusWilayah(Wilayah $wilayah)
    {
        $wilayah->delete();

        return back()->with('sukses', "Daerah {$wilayah->nama} dihapus. Riwayat data sensornya tetap tersimpan.");
    }

    // Luas bangun (rumus tali sepatu / shoelace). Nol berarti titik-titiknya segaris.
    private function luas(array $p): float
    {
        $jumlah = 0;
        $n = count($p);
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $jumlah += $p[$i][0] * $p[$j][1] - $p[$j][0] * $p[$i][1];
        }

        return abs($jumlah) / 2;
    }

    // Apakah ada dua sisi yang saling menyilang?
    private function berpotongan(array $p): bool
    {
        $n = count($p);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($j === $i + 1 || ($i === 0 && $j === $n - 1)) {
                    continue; // sisi bersebelahan boleh bertemu di sudut
                }
                if ($this->sisiMenyilang($p[$i], $p[($i + 1) % $n], $p[$j], $p[($j + 1) % $n])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function sisiMenyilang($a, $b, $c, $d): bool
    {
        $arah = fn ($p, $q, $r) => ($q[0] - $p[0]) * ($r[1] - $p[1]) - ($q[1] - $p[1]) * ($r[0] - $p[0]);

        return $arah($a, $b, $c) * $arah($a, $b, $d) < 0 && $arah($c, $d, $a) * $arah($c, $d, $b) < 0;
    }
}
