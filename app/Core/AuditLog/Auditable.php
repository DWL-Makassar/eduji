<?php

declare(strict_types=1);

namespace App\Core\AuditLog;

/**
 * Tambahkan ke model mana pun untuk mencatat created/updated/deleted.
 * Kolom di $hidden dan $auditExclude (default: password, remember_token) tidak pernah dicatat.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => AuditLog::record('created', $model, [], $model->auditValues($model->getAttributes())));

        static::updated(function ($model): void {
            $new = $model->auditValues($model->getChanges());
            unset($new['updated_at']);

            if ($new === []) {
                return;
            }

            $old = array_intersect_key($model->auditValues($model->getRawOriginal()), $new);
            AuditLog::record('updated', $model, $old, $new);
        });

        static::deleted(fn ($model) => AuditLog::record('deleted', $model, $model->auditValues($model->getAttributes())));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function auditValues(array $values): array
    {
        $excluded = array_merge($this->getHidden(), $this->auditExclude ?? ['password', 'remember_token']);

        return array_diff_key($values, array_flip($excluded));
    }
}
