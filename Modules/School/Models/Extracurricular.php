<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;

class Extracurricular extends SchoolRecord
{
    protected $table = 'school_extracurriculars';

    protected $fillable = ['name', 'coach', 'schedule', 'description', 'image_media_id', 'order', 'is_active'];

    public function toCard(): array
    {
        return [
            'name' => $this->name,
            'coach' => $this->coach,
            'schedule' => $this->schedule,
            'description' => $this->description,
            'photo' => Presenter::media($this->image),
        ];
    }
}
