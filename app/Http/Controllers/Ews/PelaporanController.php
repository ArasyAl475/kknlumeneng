<?php

namespace App\Http\Controllers\Ews;

use App\Http\Controllers\Controller;
use App\Services\Ews\NomorPelaporan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PelaporanController extends Controller
{
    public function nomor(Request $request): Response
    {
        $isi = json_encode(NomorPelaporan::data(), JSON_UNESCAPED_SLASHES);

        $respons = response($isi, 200, ['Content-Type' => 'application/json']);
        $respons->setEtag(md5($isi));
        $respons->setPublic();
        // Nomor jarang berubah: cache 1 menit menghemat kuota HP, dan nomor baru tetap cepat terpakai.
        $respons->setMaxAge(60);
        $respons->isNotModified($request);

        return $respons;
    }
}
