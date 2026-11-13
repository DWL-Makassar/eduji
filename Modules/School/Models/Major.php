<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;

class Major extends SchoolRecord
{
    protected $table = 'school_majors';

    protected $fillable = ['name', 'code', 'description', 'image_media_id', 'order', 'is_active'];

    public function toCard(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'photo' => Presenter::media($this->image),
        ];
    }
}
