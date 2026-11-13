<?php

declare(strict_types=1);

namespace Modules\School\Models;

use App\Website\Http\Presenter;
use Illuminate\Database\Eloquent\Builder;

class Alumnus extends SchoolRecord
{
    protected $table = 'school_alumni';

    /** Kontak pribadi tidak disalin ke audit log (akan tertinggal di sana walau data alumni dihapus). */
    protected array $auditExclude = ['email', 'phone'];

    protected $fillable = ['name', 'graduation_year', 'activity', 'institution', 'testimony', 'email', 'phone', 'image_media_id', 'order', 'is_active'];

    /** Angkatan terbaru dulu. @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('graduation_year')->orderBy('order')->latest('id');
    }

    /** Tanpa email/telepon: kontak alumni hanya untuk admin. */
    public function toCard(): array
    {
        return [
            'name' => $this->name,
            'graduation_year' => $this->graduation_year,
            'activity' => $this->activity,
            'institution' => $this->institution,
            'testimony' => $this->testimony,
            'photo' => Presenter::media($this->image),
        ];
    }
}
