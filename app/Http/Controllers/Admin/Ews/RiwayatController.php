<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\RiwayatRequest;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatController extends Controller
{
    private const KOLOM_CSV = ['id', 'waktu', 'wilayah', 'kelembapan_tanah', 'intensitas_hujan', 'curah_hujan_lama', 'kemiringan', 'kelembapan_udara', 'kabel_putus', 'status', 'pemicu', 'validasi'];

    public function index(RiwayatRequest $request): View
    {
        return view('admin.ews.riwayat', [
            'pembacaan' => $this->query($request)->with('wilayah')->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->withQueryString(),
            'wilayah' => Wilayah::withTrashed()->orderBy('nama')->get(['id', 'nama', 'deleted_at']),
        ]);
    }

    public function ekspor(RiwayatRequest $request): StreamedResponse
    {
        $query = $this->query($request)->with('wilayah');

        // Dialirkan per potongan: rentang 90 hari bisa ratusan ribu baris, tidak boleh dimuat sekaligus.
        return response()->streamDownload(function () use ($query) {
            $keluaran = fopen('php://output', 'w');
            fputcsv($keluaran, self::KOLOM_CSV, escape: '');

            foreach ($query->lazyByIdDesc(500) as $p) {
                fputcsv($keluaran, [
                    $p->id,
                    $p->created_at->format('Y-m-d H:i:s'),
                    self::selAman($p->wilayah->nama),
                    $p->kelembapan_tanah,
                    $p->intensitas_hujan,
                    $p->curah_hujan,
                    $p->kemiringan,
                    $p->kelembapan_udara,
                    $p->kabel_putus ? 1 : 0,
                    $p->status,
                    self::selAman($p->pemicu),
                    $p->validasi,
                ], escape: '');
            }

            fclose($keluaran);
        }, 'riwayat-ews-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(RiwayatRequest $request): Builder
    {
        return Pembacaan::query()
            ->when($request->validated('wilayah'), fn ($q, $id) => $q->where('wilayah_id', $id))
            ->when($request->validated('dari'), fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($request->validated('sampai'), fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d)->endOfDay()));
    }

    // Nama buatan admin bisa berawalan "=": tanpa ini Excel akan menjalankannya sebagai rumus.
    private static function selAman(?string $teks): ?string
    {
        return $teks !== null && preg_match('/^[=+\-@\t\r]/', $teks) === 1 ? "'{$teks}" : $teks;
    }
}
