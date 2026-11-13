<?php

declare(strict_types=1);

namespace App\Core\Media\Rules;

use App\Core\Media\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Id harus entri pustaka media yang berkasnya gambar (dipakai untuk logo, favicon, gambar OG, dst). */
final class ImageAsset implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $mime = MediaAsset::find($value)?->file()?->mime_type;

        if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
            $fail(__('settings.must_be_image'));
        }
    }
}
