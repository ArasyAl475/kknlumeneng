<?php

namespace App\Services\Edukasi;

class TautanYoutube
{
    /**
     * Mengambil kode video dari tautan YouTube yang lazim ditempel admin: watch, youtu.be,
     * shorts, embed, atau live. Selain YouTube dikembalikan null.
     */
    public static function kode(?string $tautan): ?string
    {
        $tautan = trim((string) $tautan);

        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $tautan)) {
            return $tautan;
        }

        $bagian = parse_url($tautan);
        $host = strtolower($bagian['host'] ?? '');
        $host = preg_replace('~^(www\.|m\.|music\.)~', '', $host);

        if (! in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            return null;
        }

        if ($host === 'youtu.be') {
            $kode = ltrim($bagian['path'] ?? '', '/');
        } elseif (preg_match('~^/(?:embed|shorts|live|v)/([^/?]+)~', $bagian['path'] ?? '', $cocok)) {
            $kode = $cocok[1];
        } else {
            parse_str($bagian['query'] ?? '', $query);
            $kode = is_string($query['v'] ?? null) ? $query['v'] : null;
        }

        return $kode !== null && preg_match('~^[A-Za-z0-9_-]{11}$~', $kode) ? $kode : null;
    }
}
