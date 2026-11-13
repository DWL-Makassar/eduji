<?php

declare(strict_types=1);

namespace App\Cms\Menus;

use App\Cms\Support\CmsRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends CmsRequest
{
    /** Tautan yang diizinkan: path relatif, anchor, http(s), mailto, tel (bukan javascript:/data:). */
    public const URL_PATTERN = '/^(\/|#|https?:\/\/|mailto:|tel:)/i';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', Rule::in(array_keys(config('cms.menu_locations'))), Rule::unique(Menu::class, 'location')->ignore($this->recordId())],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.url' => ['required', 'string', 'max:2048', 'regex:'.self::URL_PATTERN],
            'items.*.target' => ['required', Rule::in(['_self', '_blank'])],
            'items.*.level' => ['required', Rule::in(['0', '1'])],
            'items.*.is_active' => ['boolean'],
        ];
    }
}
