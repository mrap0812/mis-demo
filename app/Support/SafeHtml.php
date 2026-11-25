<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/** Pembersih HTML dari editor teks sebelum ditampilkan ke publik. */
final class SafeHtml
{
    public static function clean(?string $html): string
    {
        static $sanitizer;
        $sanitizer ??= new HtmlSanitizer((new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->allowRelativeMedias());

        return $sanitizer->sanitize((string) $html);
    }

    /** Nomor tampilan (0811-500-822) menjadi tautan WhatsApp (https://wa.me/62811500822). */
    public static function wa(?string $number): ?string
    {
        $d = self::intl($number);

        return $d ? 'https://wa.me/'.$d : null;
    }

    /** Nomor dalam format internasional tanpa tanda plus: 0811-500-822 menjadi 62811500822. */
    public static function intl(?string $number): ?string
    {
        $d = self::digits($number);
        if ($d === '') {
            return null;
        }

        return str_starts_with($d, '0') ? '62'.substr($d, 1) : $d;
    }

    public static function digits(?string $number): string
    {
        return preg_replace('/\D+/', '', (string) $number);
    }
}
