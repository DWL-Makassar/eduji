<?php

declare(strict_types=1);

namespace App\Cms\Contact;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'message', 'is_read', 'ip_hash'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
