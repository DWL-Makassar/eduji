<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;

class Staff extends SchoolRecord
{
    public const TYPES = ['teacher', 'staff'];

    protected $table = 'school_staff';

    protected $fillable = ['name', 'type', 'position', 'subject', 'nip', 'bio', 'image_media_id', 'order', 'is_active'];

    public function toCard(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'position' => $this->position,
            'subject' => $this->subject,
            'photo' => Presenter::media($this->image),
        ];
    }
}
