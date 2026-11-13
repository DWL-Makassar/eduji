<?php

declare(strict_types=1);

namespace App\Core\Licensing;

/**
 * Edisi build ini. Repositori selalu `full` (tanpa lisensi). Perintah pengemasan edisi gratis mengganti NAME menjadi
 * `free` lalu mengenkode berkas ini dengan ionCube, sehingga pembeli tidak bisa mengubahnya kembali.
 *
 * PUBLIC_KEY = kunci publik penjual untuk memverifikasi kunci lisensi. Kunci privatnya disimpan penjual di luar
 * repositori (folder sekolah-saas-license) dan TIDAK PERNAH ikut ke aplikasi.
 */
final class Edition
{
    public const NAME = 'free';

    public const PUBLIC_KEY = <<<'PEM'
        -----BEGIN PUBLIC KEY-----
        MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEJ0rPkbW7B+ud+cVT0qrgjk5vPAKY
        UeudUJbhkvJ1BNStmOfSIPgGaknSru+F3/+1dpoxxkoes6Syspf7ubolng==
        -----END PUBLIC KEY-----
        PEM;

    public static function requiresLicense(): bool
    {
        return self::NAME === 'free';
    }
}
