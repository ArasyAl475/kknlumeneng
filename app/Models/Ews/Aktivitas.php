<?php

namespace App\Models\Ews;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Aktivitas extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ews_aktivitas';

    protected $fillable = ['user_id', 'nama_admin', 'aksi', 'keterangan'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function catat(User $admin, string $aksi, string $keterangan): void
    {
        static::query()->create([
            'user_id' => $admin->id,
            'nama_admin' => $admin->name,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
        ]);
    }
}
