<?php

namespace Tests\Feature\Fondasi;

use App\Models\Desain\SlotGambar;
use App\Models\User;
use Database\Seeders\DesainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogoDanMenuPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DesainSeeder::class);
    }

    public function test_logo_bawaan_dipakai_di_tab_dan_header(): void
    {
        $logo = asset('images/default/situs-logo.svg');

        $this->assertFileExists(public_path('images/default/situs-logo.svg'));
        $this->get('/')->assertOk()
            ->assertSee('<link rel="icon" href="'.$logo.'">', false)
            ->assertSee('<img src="'.$logo.'" alt=""', false);
        $this->get(route('login'))->assertSee('<link rel="icon" href="'.$logo.'">', false);
    }

    public function test_logo_unggahan_admin_mengganti_ikon_tab_dan_logo_di_semua_kerangka(): void
    {
        $admin = User::factory()->admin()->create();
        $slot = SlotGambar::query()->where('kunci', 'situs.logo')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.desain.index'))->assertSee('Logo desa');
        $this->put(route('admin.desain.update', $slot), [
            'gambar' => UploadedFile::fake()->image('logo.png', 512, 512),
        ])->assertSessionHas('sukses');

        $baru = asset('storage/'.$slot->refresh()->path);

        $this->get(route('admin.beranda'))->assertSee('<link rel="icon" href="'.$baru.'">', false)->assertSee('<img src="'.$baru.'"', false);
        auth()->logout();
        $this->get('/')->assertSee('<link rel="icon" href="'.$baru.'">', false)->assertSee('<img src="'.$baru.'"', false);
        $this->get(route('login'))->assertSee('<img src="'.$baru.'"', false);
        $this->get(route('toko.index'))->assertSee('<link rel="icon" href="'.$baru.'">', false);
    }

    public function test_halaman_publik_tidak_menautkan_panel_admin(): void
    {
        foreach ([null, User::factory()->admin()->create()] as $pengguna) {
            if ($pengguna) {
                $this->actingAs($pengguna);
            }

            $this->get('/')->assertOk()
                ->assertDontSee('Panel Admin')
                ->assertDontSee('Masuk Admin')
                ->assertDontSee('href="'.route('admin.beranda').'"', false)
                ->assertDontSee('href="'.route('login').'"', false);
        }
    }

    public function test_panel_admin_tetap_terbuka_lewat_alamat_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }

    public function test_menu_hp_punya_animasi_geser_yang_menghormati_kurangi_gerak(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@keyframes menu-hp-masuk', $isi);
        $this->assertStringContainsString('prefers-reduced-motion: no-preference', $isi);
        $this->assertSame(1, substr_count($isi, '@keyframes menu-hp-masuk'));
    }
}
