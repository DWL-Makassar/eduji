<?php

declare(strict_types=1);

namespace App\Cms\Menus;

use App\Core\AuditLog\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use Auditable;

    protected $fillable = ['name', 'location'];

    /** Semua item, urut. Pohon (maks. 2 level) disusun dari parent_id. */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /** Hapus semua item. Tanpa ORDER BY: di MySQL, DELETE ... ORDER BY pada tabel dengan cascade ke dirinya sendiri menyisakan baris. */
    public function clearItems(): void
    {
        $this->items()->reorder()->delete();
    }
}
