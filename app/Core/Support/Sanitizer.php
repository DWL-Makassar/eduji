<?php

declare(strict_types=1);

namespace App\Core\Support;

use Mews\Purifier\Facades\Purifier;

/**
 * Satu-satunya pintu sanitasi HTML dari editor rich text (profil "cms" di config/purifier.php).
 * Simpan hasil sanitasi ke database; frontend hanya boleh merender lewat SafeHtml.
 */
final class Sanitizer
{
    public static function html(?string $html): string
    {
        return $html === null || $html === '' ? '' : (string) Purifier::clean($html, 'cms');
    }
}
