<?php

namespace App\Models\Profil;

use App\Services\Profil\PenyimpanGambar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perangkat extends Model
{
    use HasFactory;

    /**
     * Urutan di sini adalah urutan tingkat pada bagan struktur, dari atas ke bawah, mengikuti
     * susunan pemerintah desa: kepala desa, sekretariat, pelaksana teknis, pelaksana kewilayahan.
     */
    public const KELOMPOK = [
        'kepala' => 'Kepala Desa',
        'sekretaris' => 'Sekretaris Desa',
        'kaur' => 'Kepala Urusan',
        'kasi' => 'Kepala Seksi',
        'kadus' => 'Kepala Dusun',
    ];

    protected $table = 'profil_perangkat';

    protected $fillable = ['nama', 'jabatan', 'kelompok', 'foto', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }

    public function urlFoto(): ?string
    {
        return PenyimpanGambar::url($this->foto);
    }

    // Pengganti foto: dua huruf awal nama, tanpa gelar seperti "H." atau "S.Pd".
    public function inisial(): string
    {
        $kata = collect(preg_split('/[\s,]+/', (string) $this->nama))
            ->reject(fn (string $k) => $k === '' || str_contains($k, '.'))
            ->values();

        return mb_strtoupper(mb_substr($kata[0] ?? 'P', 0, 1).mb_substr($kata[1] ?? '', 0, 1));
    }

    public function labelKelompok(): string
    {
        return self::KELOMPOK[$this->kelompok] ?? $this->kelompok;
    }
}
