<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\RekapRequest;
use App\Services\Toko\EksporExcelHasil;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EksporController extends Controller
{
    /**
     * Unduh Excel dengan filter yang sama seperti halaman Hasil Panen.
     */
    public function __invoke(RekapRequest $request, EksporExcelHasil $ekspor): StreamedResponse
    {
        $buku = $ekspor->buat($request->validated());

        return response()->streamDownload(
            fn () => (new Xlsx($buku))->save('php://output'),
            $ekspor->namaBerkas(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
