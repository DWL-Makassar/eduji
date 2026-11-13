<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;

class Unit extends SchoolRecord
{
    public const LEVELS = ['tk', 'sd', 'smp', 'sma', 'smk', 'other'];

    protected $table = 'school_units';

    protected $fillable = ['name', 'level', 'npsn', 'principal', 'phone', 'address', 'url', 'image_media_id', 'order', 'is_active'];

    public function toCard(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level ? __("school::messages.unit_levels.{$this->level}") : null,
            'npsn' => $this->npsn,
            'principal' => $this->principal,
            'phone' => $this->phone,
            'address' => $this->address,
            'url' => $this->url,
            'logo' => Presenter::media($this->image),
        ];
    }
}
