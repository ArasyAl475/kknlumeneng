<?php

namespace Tests\Unit\Edukasi;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Services\Edukasi\BagianKategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagianKategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_menentukan_posisi_sebelumnya_dan_berikutnya_menurut_nomor_bagian(): void
    {
        $kategori = Kategori::factory()->create();
        // Sengaja dibuat tidak berurutan agar terbukti yang dipakai nomor bagian, bukan urutan pembuatan.
        $ketiga = Materi::factory()->terbit()->for($kategori)->create(['urutan' => 3]);
        $pertama = Materi::factory()->terbit()->for($kategori)->create(['urutan' => 1]);
        $kedua = Materi::factory()->terbit()->for($kategori)->create(['urutan' => 2]);

        $tengah = BagianKategori::untuk($kedua);

        $this->assertSame(2, $tengah['posisi']);
        $this->assertSame(3, $tengah['total']);
        $this->assertTrue($tengah['sebelumnya']->is($pertama));
        $this->assertTrue($tengah['berikutnya']->is($ketiga));
        $this->assertNull(BagianKategori::untuk($pertama)['sebelumnya']);
        $this->assertNull(BagianKategori::untuk($ketiga)['berikutnya']);
    }

    public function test_draf_dan_kategori_lain_tidak_dihitung(): void
    {
        $kategori = Kategori::factory()->create();
        $pertama = Materi::factory()->terbit()->for($kategori)->create(['urutan' => 1]);
        Materi::factory()->for($kategori)->create(['urutan' => 2]);
        Materi::factory()->terbit()->create(['urutan' => 3]);
        $ketiga = Materi::factory()->terbit()->for($kategori)->create(['urutan' => 4]);

        $hasil = BagianKategori::untuk($pertama);

        $this->assertSame(2, $hasil['total']);
        $this->assertTrue($hasil['berikutnya']->is($ketiga));
    }

    public function test_draf_yang_dipratinjau_tidak_punya_nomor_bagian(): void
    {
        $draf = Materi::factory()->create(['urutan' => 1]);

        $hasil = BagianKategori::untuk($draf);

        $this->assertNull($hasil['posisi']);
        $this->assertNull($hasil['sebelumnya']);
        $this->assertNull($hasil['berikutnya']);
    }
}
