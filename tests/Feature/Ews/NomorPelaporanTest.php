<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Aktivitas;
use App\Models\User;
use App\Services\Ews\NomorPelaporan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomorPelaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_tetap_berbentuk_sama_saat_nomor_belum_diisi(): void
    {
        $this->getJson(route('ews.api.pelaporan'))
            ->assertOk()
            ->assertExactJson(['tersedia' => false, 'nomor' => null, 'nomor_tampil' => null, 'tautan_wa' => null]);
    }

    public function test_nomor_dari_admin_langsung_terbaca_aplikasi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.ews.pelaporan.edit'))->assertOk()->assertSee('Belum ada nomor');
        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => '0812-3456-7890'])->assertSessionHas('sukses');

        $this->getJson(route('ews.api.pelaporan'))
            ->assertOk()
            ->assertExactJson([
                'tersedia' => true,
                'nomor' => '6281234567890',
                'nomor_tampil' => '0812-3456-7890',
                'tautan_wa' => 'https://wa.me/6281234567890',
            ]);

        $this->get(route('admin.ews.pelaporan.edit'))->assertSee('0812-3456-7890');
    }

    public function test_ganti_nomor_dicatat_dan_simpan_ulang_tidak_mengotori_log(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => '081234567890']);
        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => '+62 812 3456 7890']);
        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => '085700001111']);

        $this->assertSame('6285700001111', NomorPelaporan::ambil());
        $this->assertSame(2, Aktivitas::query()->count());
        $this->assertStringContainsString('0812-3456-7890 menjadi 0857-0000-1111', Aktivitas::query()->latest('id')->first()->keterangan);
    }

    public function test_nomor_salah_ditolak_dan_nomor_lama_tetap(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        NomorPelaporan::simpan('6281234567890');

        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => '0285123456'])->assertSessionHasErrors('nomor_wa');
        $this->put(route('admin.ews.pelaporan.update'), ['nomor_wa' => ''])->assertSessionHasErrors('nomor_wa');

        $this->assertSame('6281234567890', NomorPelaporan::ambil());
    }

    public function test_api_mendukung_cache_agar_hemat_kuota(): void
    {
        NomorPelaporan::simpan('6281234567890');

        $etag = $this->get(route('ews.api.pelaporan'))->headers->get('ETag');

        $this->get(route('ews.api.pelaporan'), ['If-None-Match' => $etag])->assertStatus(304);
    }
}
