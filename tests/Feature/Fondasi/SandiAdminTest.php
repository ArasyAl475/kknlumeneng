<?php

namespace Tests\Feature\Fondasi;

use App\Http\Controllers\Auth\LupaSandiController;
use App\Models\User;
use App\Notifications\AturUlangSandi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SandiAdminTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI_LAMA = 'tebing-Hijau-7-rimba';

    private const SANDI_BARU = 'kabut-Pagi-42-lereng';

    private function admin(): User
    {
        return User::factory()->admin()->create(['email' => 'admin@contoh.test', 'password' => self::SANDI_LAMA]);
    }

    // ───────────────────────────── Ganti kata sandi (sudah masuk) ─────────────────────────────

    public function test_sidebar_admin_punya_tautan_ganti_kata_sandi(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.beranda'))
            ->assertOk()
            ->assertSee('Ganti kata sandi')
            ->assertSee(route('admin.akun.sandi.edit'), false);
    }

    public function test_halaman_ganti_kata_sandi_hanya_untuk_admin(): void
    {
        $this->get(route('admin.akun.sandi.edit'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.akun.sandi.edit'))->assertForbidden();
    }

    public function test_admin_bisa_mengganti_kata_sandi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.akun.sandi.edit'))
            ->assertOk()
            ->assertSee('Kata sandi lama');

        $this->put(route('admin.akun.sandi.update'), [
            'sandi_lama' => self::SANDI_LAMA,
            'password' => self::SANDI_BARU,
            'password_confirmation' => self::SANDI_BARU,
        ])->assertRedirect(route('admin.akun.sandi.edit'))->assertSessionHas('sukses');

        $this->assertTrue(Hash::check(self::SANDI_BARU, $admin->fresh()->password));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_sandi_lama_salah_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.akun.sandi.edit'))
            ->put(route('admin.akun.sandi.update'), [
                'sandi_lama' => 'bukan-sandinya-99',
                'password' => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
            ])
            ->assertRedirect(route('admin.akun.sandi.edit'))
            ->assertSessionHasErrors(['sandi_lama' => 'Kata sandi lama salah.']);

        $this->assertTrue(Hash::check(self::SANDI_LAMA, $admin->fresh()->password));
    }

    public function test_sandi_baru_lemah_atau_tidak_sama_ditolak(): void
    {
        $this->actingAs($this->admin());

        foreach (['pendek1', 'hanyahurufsaja', '1234567890123', 'admin-desa-2026x'] as $lemah) {
            $this->put(route('admin.akun.sandi.update'), [
                'sandi_lama' => self::SANDI_LAMA,
                'password' => $lemah,
                'password_confirmation' => $lemah,
            ])->assertSessionHasErrors('password');
        }

        $this->put(route('admin.akun.sandi.update'), [
            'sandi_lama' => self::SANDI_LAMA,
            'password' => self::SANDI_BARU,
            'password_confirmation' => 'berbeda-Total-55',
        ])->assertSessionHasErrors('password');

        $this->put(route('admin.akun.sandi.update'), [
            'sandi_lama' => self::SANDI_LAMA,
            'password' => self::SANDI_LAMA,
            'password_confirmation' => self::SANDI_LAMA,
        ])->assertSessionHasErrors('password');
    }

    // ───────────────────────────── Lupa kata sandi ─────────────────────────────

    public function test_halaman_masuk_punya_tautan_lupa_kata_sandi(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false)->assertSee('Lupa kata sandi?');
        $this->get(route('password.request'))->assertOk()->assertSee('Kirim tautan ke email');
    }

    public function test_admin_menerima_email_berisi_tautan_atur_ulang(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('sukses', LupaSandiController::PESAN_TERKIRIM);

        Notification::assertSentTo($admin, AturUlangSandi::class, function (AturUlangSandi $notifikasi) use ($admin) {
            $surat = $notifikasi->toMail($admin);

            return str_starts_with($surat->actionUrl, url('/atur-ulang-sandi/'))
                && str_contains($surat->actionUrl, 'email='.urlencode($admin->email))
                && $surat->subject !== null && str_contains($surat->subject, 'kata sandi baru');
        });
    }

    public function test_email_tak_terdaftar_dan_bukan_admin_mendapat_pesan_sama_tanpa_email(): void
    {
        Notification::fake();
        $warga = User::factory()->create(['email' => 'warga@contoh.test']);

        foreach (['tidak-ada@contoh.test', $warga->email] as $email) {
            $this->from(route('password.request'))
                ->post(route('password.email'), ['email' => $email])
                ->assertSessionHas('sukses', LupaSandiController::PESAN_TERKIRIM);
        }

        Notification::assertNothingSent();
    }

    public function test_gagal_kirim_email_tidak_membuat_halaman_galat(): void
    {
        $this->admin();
        // Pengaturan surat yang rusak, seperti MAIL_PASSWORD Gmail yang salah.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'admin@contoh.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('sukses', LupaSandiController::PESAN_TERKIRIM);
    }

    public function test_permintaan_lupa_sandi_dibatasi(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $percobaan) {
            $this->post(route('password.email'), ['email' => 'siapa@contoh.test']);
        }

        $this->post(route('password.email'), ['email' => 'siapa@contoh.test'])->assertStatus(429);
    }

    public function test_tautan_dari_email_mengganti_sandi_dan_bisa_masuk(): void
    {
        $admin = $this->admin();
        $token = Password::createToken($admin);

        $this->get(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Buat kata sandi baru')
            ->assertSee($admin->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => self::SANDI_BARU,
            'password_confirmation' => self::SANDI_BARU,
        ])->assertRedirect(route('login'))->assertSessionHas('sukses');

        $this->assertTrue(Hash::check(self::SANDI_BARU, $admin->fresh()->password));
        $this->assertGuest();

        $this->post(route('masuk.proses'), ['email' => $admin->email, 'password' => self::SANDI_BARU])
            ->assertRedirect(route('admin.beranda'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_tautan_hanya_bisa_dipakai_sekali(): void
    {
        $admin = $this->admin();
        $token = Password::createToken($admin);
        $isian = ['token' => $token, 'email' => $admin->email, 'password' => self::SANDI_BARU, 'password_confirmation' => self::SANDI_BARU];

        $this->post(route('password.update'), $isian)->assertRedirect(route('login'));

        $this->from(route('password.reset', $token))
            ->post(route('password.update'), [...$isian, 'password' => 'lain-Lagi-31-bukit', 'password_confirmation' => 'lain-Lagi-31-bukit'])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::SANDI_BARU, $admin->fresh()->password));
    }

    public function test_token_palsu_dan_sandi_lemah_ditolak(): void
    {
        $admin = $this->admin();

        $this->from(route('password.reset', 'palsu'))
            ->post(route('password.update'), [
                'token' => 'token-palsu',
                'email' => $admin->email,
                'password' => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
            ])
            ->assertSessionHasErrors('email');

        $this->post(route('password.update'), [
            'token' => Password::createToken($admin),
            'email' => $admin->email,
            'password' => 'lumeneng2026aman',
            'password_confirmation' => 'lumeneng2026aman',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::SANDI_LAMA, $admin->fresh()->password));
    }

    public function test_admin_yang_sudah_masuk_tidak_perlu_halaman_lupa_sandi(): void
    {
        $this->actingAs($this->admin())->get(route('password.request'))->assertRedirect(route('admin.beranda'));
    }
}
