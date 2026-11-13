<?php

declare(strict_types=1);

namespace App\Cms\Support;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis validasi resource CMS. Otorisasi (permission) ditegakkan di CrudController
 * sebelum request ini di-resolve, jadi authorize() di sini selalu true.
 */
abstract class CmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** ID record yang sedang di-update (parameter route pertama), null saat create. */
    protected function recordId(): ?string
    {
        $parameters = $this->route()?->parameters() ?? [];

        return $parameters === [] ? null : (string) reset($parameters);
    }
}
