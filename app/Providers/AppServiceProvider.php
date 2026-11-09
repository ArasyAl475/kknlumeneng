<?php

namespace App\Providers;

use App\Services\Desain\GambarSitus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Satu salinan per permintaan, agar semua slot gambar cukup dibaca dengan satu kueri.
        $this->app->singleton(GambarSitus::class);
    }

    public function boot(): void
    {
        // Menangkap N+1 lebih awal saat pengembangan, tanpa risiko galat bagi warga di produksi.
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Dua lapis: per akun+alamat menahan tebakan sandi, per alamat menahan tebakan banyak akun.
        RateLimiter::for('masuk', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
    }
}
