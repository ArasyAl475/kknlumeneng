<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\ImporHasilRequest;
use App\Models\Toko\Aktivitas;
use App\Services\Toko\ImporExcelHasil;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImporHasilController extends Controller
{
    /**
     * Baris yang sudah lolos pemeriksaan ditaruh di sesi (di server, bukan di browser) di antara
     * pratinjau dan tombol Simpan. Satu kali pakai: refresh/ulang tekan Simpan tidak menggandakan data.
     */
    private function kunciSesi(string $jenis): string
    {
        return "impor_hasil.{$jenis}";
    }

    public function show(string $jenis, ImporExcelHasil $impor): View
    {
        return view('admin.toko.impor', $this->bahanTampilan($jenis, $impor));
    }

    public function templat(string $jenis, ImporExcelHasil $impor): StreamedResponse
    {
        $buku = $impor->templat($jenis);

        return response()->streamDownload(
            fn () => (new Xlsx($buku))->save('php://output'),
            $impor->namaTemplat($jenis),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function pratinjau(ImporHasilRequest $request, string $jenis, ImporExcelHasil $impor): View|RedirectResponse
    {
        $request->session()->forget($this->kunciSesi($jenis));

        try {
            $hasil = $impor->baca($jenis, $request->file('berkas')->getRealPath());
        } catch (DomainException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }

        if ($hasil['jumlah_galat'] === 0) {
            $request->session()->put($this->kunciSesi($jenis), array_column($hasil['baris'], 'data'));
        }

        return view('admin.toko.impor', $this->bahanTampilan($jenis, $impor) + ['hasil' => $hasil]);
    }

    public function simpan(Request $request, string $jenis, ImporExcelHasil $impor): RedirectResponse
    {
        $data = $request->session()->pull($this->kunciSesi($jenis));

        if (! is_array($data) || $data === []) {
            return to_route('admin.toko.impor.show', $jenis)->with('galat', 'Tidak ada data yang menunggu disimpan. Unggah berkasnya lagi.');
        }

        $jumlah = $impor->simpan($jenis, $data);
        $label = mb_strtolower($impor->definisi($jenis)['label']);
        Aktivitas::catat($request->user(), 'impor_hasil', "Mengimpor {$jumlah} catatan {$label} dari Excel.");

        return to_route("admin.toko.{$jenis}.index")->with('sukses', "{$jumlah} catatan {$label} berhasil ditambahkan dari Excel.");
    }

    private function bahanTampilan(string $jenis, ImporExcelHasil $impor): array
    {
        return [
            'jenis' => $jenis,
            'def' => $impor->definisi($jenis),
            'maksBaris' => ImporExcelHasil::MAKS_BARIS,
        ];
    }
}
