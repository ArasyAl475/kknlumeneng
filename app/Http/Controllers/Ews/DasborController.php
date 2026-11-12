<?php

namespace App\Http\Controllers\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\GrafikRequest;
use App\Models\Ews\Wilayah;
use App\Services\Ews\DataDasbor;
use App\Services\Ews\DataGrafik;
use App\Services\Ews\PetaKerawanan;
use App\Services\Ews\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DasborController extends Controller
{
    public function index(DataDasbor $data): View
    {
        return view('ews.index', [
            'data' => $data->ambil(),
            'sensor' => Sensor::DAFTAR,
            'petaKerawanan' => PetaKerawanan::peta(),
        ]);
    }

    public function data(Request $request, DataDasbor $data): Response
    {
        ['isi' => $isi, 'etag' => $etag] = $data->terkemas();

        $respons = response($isi, 200, ['Content-Type' => 'application/json']);
        $respons->setEtag($etag);
        $respons->setPublic();
        $respons->setMaxAge(5);

        // 304 berarti HP warga tidak perlu mengunduh ulang data yang sama: hemat kuota saat sinyal lemah.
        $respons->isNotModified($request);

        return $respons;
    }

    public function grafik(GrafikRequest $request, DataGrafik $grafik): JsonResponse
    {
        return response()->json($grafik->ambil(
            Wilayah::query()->findOrFail($request->integer('wilayah')),
            $request->string('sensor')->value(),
            $request->string('rentang')->value(),
        ));
    }
}
