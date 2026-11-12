<?php

namespace Tests\Unit\Profil;

use App\Services\Profil\IsiKaya;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IsiKayaTest extends TestCase
{
    public function test_format_editor_dipertahankan(): void
    {
        $html = '<h2>Judul</h2><h3>Sub</h3><p><strong>tebal</strong> <em>miring</em> <u>garis</u> <s>coret</s></p>'
            .'<ol><li>satu</li></ol><ul><li class="ql-indent-1">dua</li></ul><blockquote>kutip</blockquote>'
            .'<p class="ql-align-center"><span class="ql-size-huge">besar</span></p><p><img src="/storage/profil/a.webp" alt="foto"></p>';

        $hasil = IsiKaya::bersihkan($html);

        foreach (['<h2>Judul</h2>', '<h3>Sub</h3>', '<strong>tebal</strong>', '<em>miring</em>', '<u>garis</u>', '<s>coret</s>', '<ol><li>satu</li></ol>', '<li class="ql-indent-1">dua</li>', '<blockquote>kutip</blockquote>', '<p class="ql-align-center"><span class="ql-size-huge">besar</span></p>', '<img src="/storage/profil/a.webp" alt="foto" />'] as $potongan) {
            $this->assertStringContainsString($potongan, $hasil);
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function isianBerbahaya(): array
    {
        return [
            'tag script' => ['<p>a</p><script>alert(1)</script>', 'alert(1)'],
            'atribut onerror' => ['<img src="/x.jpg" onerror="alert(1)">', 'onerror'],
            'tautan javascript' => ['<a href="javascript:alert(1)">klik</a>', 'javascript:'],
            'iframe' => ['<iframe src="https://jahat.example"></iframe>', 'iframe'],
            'gaya sebaris' => ['<p style="position:fixed;inset:0">x</p>', 'style'],
            'kelas tata letak situs' => ['<p class="fixed inset-0 z-50">x</p>', 'fixed'],
            'gambar data' => ['<img src="data:image/svg+xml;base64,PHN2Zz4=">', 'data:'],
            'svg' => ['<svg><script>alert(1)</script></svg>', 'svg'],
        ];
    }

    #[DataProvider('isianBerbahaya')]
    public function test_isian_berbahaya_dibuang(string $masukan, string $tidakBolehAda): void
    {
        $this->assertStringNotContainsString($tidakBolehAda, IsiKaya::bersihkan($masukan));
    }

    public function test_tautan_keluar_dibuka_aman(): void
    {
        $this->assertSame(
            '<p><a href="https://desa.id" target="_blank" rel="noopener noreferrer nofollow">desa</a></p>',
            IsiKaya::bersihkan('<p><a href="https://desa.id">desa</a></p>'),
        );
    }

    public function test_tag_asing_dibuang_tetapi_teksnya_tetap(): void
    {
        $this->assertSame('Teks dari Word', IsiKaya::bersihkan('<div><font face="Arial">Teks dari Word</font></div>'));
    }

    public function test_teks_biasa_menjadi_paragraf_aman(): void
    {
        $this->assertSame('<p>Baris satu<br />baris dua</p><p>A &amp; B</p>', IsiKaya::bersihkan("Baris satu\nbaris dua\n\nA & B"));
    }

    public function test_isi_kosong_dari_editor_dianggap_kosong(): void
    {
        $this->assertSame('', IsiKaya::bersihkan('<p><br></p>'));
        $this->assertSame('', IsiKaya::bersihkan('<p>&nbsp; </p>'));
        $this->assertNotSame('', IsiKaya::bersihkan('<p><img src="/storage/profil/a.webp"></p>'));
    }

    public function test_teks_polos_untuk_hitungan_kata(): void
    {
        $this->assertSame('Judul Isi satu dua', IsiKaya::teks('<h2>Judul</h2><p>Isi&nbsp;satu</p><ul><li>dua</li></ul>'));
    }
}
