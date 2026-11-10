<?php

namespace App\Services\Profil;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Editor menandai ukuran huruf, rata teks, dan indentasi dengan kelas ql-*. Hanya kelas itu yang
 * boleh lolos, agar isi berita tidak bisa memakai kelas tata letak situs (misalnya menutupi layar).
 */
final class SaringKelasIsi implements AttributeSanitizerInterface
{
    private const POLA = '/^ql-(size-(small|large|huge)|align-(center|right|justify)|indent-[1-8])$/';

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['class'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $kelas = array_filter(preg_split('/\s+/', trim($value)) ?: [], fn (string $k) => preg_match(self::POLA, $k) === 1);

        return $kelas ? implode(' ', $kelas) : null;
    }
}
