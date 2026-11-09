<?php

use App\Http\Middleware\PastikanAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Perlindungan dipasang di sini, bukan di tiap berkas modul, agar rute admin baru
            // tidak mungkin lupa dilindungi. Tidak ada saklar .env untuk mematikannya.
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => PastikanAdmin::class]);
        $middleware->redirectUsersTo(fn () => route('admin.beranda'));

        // Cloudflare Tunnel meneruskan pengunjung dari laptop sendiri (127.0.0.1). Hanya alamat itu
        // yang dipercaya, agar skema https dan IP asli pengunjung terbaca tanpa bisa dipalsukan dari luar.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // Hak akses diperiksa sebelum data rute dicari. Jika terbalik, non-admin bisa menebak
        // ID mana yang ada dari beda jawaban 404 dan 403.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: PastikanAdmin::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
