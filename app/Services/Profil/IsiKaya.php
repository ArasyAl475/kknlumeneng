<?php

namespace App\Services\Profil;

use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Isi berformat (tebal, miring, daftar, foto) dari editor admin. Disimpan sebagai HTML, jadi
 * WAJIB lewat pembersih ini sebelum disimpan dan sebelum ditampilkan: hanya tag dan atribut
 * dalam daftar izin yang lolos, sehingga skrip atau tautan javascript: tidak pernah sampai ke warga.
 */
final class IsiKaya
{
    private static ?HtmlSanitizer $pembersih = null;

    public static function bersihkan(?string $isi): string
    {
        $html = self::keHtml($isi);

        if ($html === '') {
            return '';
        }

        $bersih = trim(self::pembersih()->sanitize($html));
        // Gambar yang sumbernya ditolak (misalnya data:) tersisa sebagai <img /> kosong; dibuang saja.
        $bersih = preg_replace('#<img(?![^>]*\ssrc=)[^>]*>#i', '', $bersih) ?? '';

        // Editor menyimpan isi kosong sebagai <p><br></p>; itu dianggap tidak diisi.
        return self::kosong($bersih) ? '' : $bersih;
    }

    /**
     * HtmlString ditampilkan Blade apa adanya lewat {{ }}. Aman karena isinya sudah melewati
     * pembersih di atas; jangan pernah membungkus teks pengguna yang belum dibersihkan.
     */
    public static function tampilkan(?string $isi): HtmlString
    {
        return new HtmlString(self::bersihkan($isi));
    }

    /**
     * Tulisan lama (dan data contoh) berupa teks biasa. Diubah menjadi paragraf HTML agar
     * tampil benar di halaman dan bisa langsung disunting di editor.
     */
    public static function keHtml(?string $isi): string
    {
        $isi = trim((string) $isi);

        if ($isi === '' || $isi !== strip_tags($isi)) {
            return $isi;
        }

        return collect(preg_split('/\R\s*\R/u', $isi) ?: [])
            ->map(fn (string $p) => trim($p))
            ->filter(fn (string $p) => $p !== '')
            // Pindah baris diganti <br> (bukan nl2br) karena isi ditampilkan dengan white-space: pre-wrap.
            ->map(fn (string $p) => '<p>'.preg_replace('/\R/u', '<br>', e($p)).'</p>')
            ->implode('');
    }

    // Teks polos untuk menghitung kata dan mencari; tag dibuang, jeda antarblok diganti spasi.
    public static function teks(?string $isi): string
    {
        $html = preg_replace('#<(br|/p|/li|/h[1-6]|/blockquote)\b[^>]*>#i', ' ', self::keHtml($isi)) ?? '';
        $teks = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $teks)) ?? '');
    }

    public static function kosong(string $html): bool
    {
        return self::teks($html) === '' && ! str_contains(strtolower($html), '<img');
    }

    private static function pembersih(): HtmlSanitizer
    {
        return self::$pembersih ??= new HtmlSanitizer(self::aturan());
    }

    private static function aturan(): HtmlSanitizerConfig
    {
        $aturan = (new HtmlSanitizerConfig)
            // Tag di luar daftar izin dibuang tetapi teksnya dipertahankan, agar tulisan yang
            // ditempel dari Word atau WhatsApp tidak hilang.
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowElement('p', ['class'])
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('span', ['class'])
            ->allowElement('h2', ['class'])
            ->allowElement('h3', ['class'])
            ->allowElement('blockquote', ['class'])
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li', ['class'])
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            // Tautan keluar dibuka di tab baru dan tidak bisa mengendalikan halaman desa.
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withAttributeSanitizer(new SaringKelasIsi)
            ->withMaxInputLength(500_000);

        // Tag berbahaya dibuang bersama seluruh isinya.
        foreach (['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'head', 'title', 'link', 'meta', 'base', 'video', 'audio'] as $tag) {
            $aturan = $aturan->dropElement($tag);
        }

        return $aturan;
    }
}
