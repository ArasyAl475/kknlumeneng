<?php

namespace App\Models\Ews;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembacaan extends Model
{
    use HasFactory;

    public const VALIDASI_MENUNGGU = 'menunggu';

    public const VALIDASI_DISETUJUI = 'disetujui';

    public const VALIDASI_DITOLAK = 'ditolak';

    public const UPDATED_AT = null;

    protected $table = 'ews_pembacaan';

    protected $fillable = [
        'wilayah_id', 'kelembapan_tanah', 'intensitas_hujan', 'kemiringan', 'kelembapan_udara',
        'kabel_putus', 'status', 'warna', 'pemicu', 'validasi', 'divalidasi_pada', 'divalidasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'kelembapan_tanah' => 'float',
            'curah_hujan' => 'float', // kolom lama (mm/jam), hanya riwayat
            'intensitas_hujan' => 'float',
            'kemiringan' => 'float',
            'kelembapan_udara' => 'float',
            'kabel_putus' => 'boolean',
            'divalidasi_pada' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // Riwayat tetap menampilkan nama wilayah walau wilayahnya sudah dihapus lunak.
    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id')->withTrashed();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }
}
