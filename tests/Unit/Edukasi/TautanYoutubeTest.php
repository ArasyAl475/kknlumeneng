<?php

namespace Tests\Unit\Edukasi;

use App\Services\Edukasi\TautanYoutube;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TautanYoutubeTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function tautan(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch dengan parameter lain' => ['https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10', 'dQw4w9WgXcQ'],
            'HP' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'tautan pendek' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'kode saja' => ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'situs lain' => ['https://contoh.com/watch?v=dQw4w9WgXcQ', null],
            'host menyerupai' => ['https://youtube.com.jahat.id/watch?v=dQw4w9WgXcQ', null],
            'kode terlalu pendek' => ['https://youtu.be/abc', null],
            'bukan tautan' => ['halo dunia', null],
            'kosong' => [null, null],
        ];
    }

    #[DataProvider('tautan')]
    public function test_kode_youtube_diambil_dari_tautan_yang_lazim(?string $tautan, ?string $harapan): void
    {
        $this->assertSame($harapan, TautanYoutube::kode($tautan));
    }
}
