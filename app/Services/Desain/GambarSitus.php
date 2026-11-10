<?php

namespace App\Services\Desain;

use App\Models\Desain\SlotGambar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Penentu gambar yang dipakai tiap slot. Urutannya: unggahan admin → gambar bawaan slot →
 * gambar cadangan umum. Halaman tidak boleh rusak hanya karena berkas hilang atau tabel belum ada.
 */
class GambarSitus
{
    /** @var Collection<string, SlotGambar>|null */
    private ?Collection $slot = null;

    public function url(string $kunci): string
    {
        $slot = $this->semua()->get($kunci);

        if ($slot?->path && Storage::disk('public')->exists($slot->path)) {
            return asset('storage/'.$slot->path);
        }

        $bawaan = $slot->gambar_bawaan ?? DaftarSlot::bawaan($kunci);

        return asset(is_file(public_path($bawaan)) ? $bawaan : DaftarSlot::CADANGAN);
    }

    public function alt(string $kunci): string
    {
        return (string) ($this->semua()->get($kunci)?->alt ?? DaftarSlot::ambil($kunci)['alt'] ?? '');
    }

    // Satu kueri untuk semua slot per permintaan, karena satu halaman bisa memakai beberapa slot.
    public function lupakan(): void
    {
        $this->slot = null;
    }

    /**
     * @return Collection<string, SlotGambar>
     */
    private function semua(): Collection
    {
        if ($this->slot !== null) {
            return $this->slot;
        }

        try {
            return $this->slot = SlotGambar::query()->get()->keyBy('kunci');
        } catch (Throwable $galat) {
            // Mis. migrasi belum dijalankan: situs tetap tampil dengan gambar bawaan.
            Log::warning('Slot gambar situs gagal dimuat: '.$galat->getMessage());

            return $this->slot = collect();
        }
    }
}
