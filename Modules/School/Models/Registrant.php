<?php

declare(strict_types=1);

namespace Modules\School\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Pendaftar SPMB. Sengaja tanpa Auditable: audit log akan menyalin data pribadi anak ke tabel lain.
 * Berkas ada di disk `local` (storage/app/private) di folder spmb/<id>, dihapus bersama record.
 */
class Registrant extends Model
{
    public const STATUSES = ['submitted', 'verified', 'accepted', 'rejected'];

    /** Jenis berkas => wajib? Pas foto harus gambar (dipakai di kartu pendaftaran). */
    public const DOCUMENTS = ['photo' => true, 'family_card' => true, 'birth_certificate' => true, 'certificate' => false];

    public const DISK = 'local';

    /** Huruf kode akses tanpa yang mirip (0/O, 1/I/L) agar mudah disalin dari layar atau kertas. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $table = 'school_registrants';

    protected $fillable = [
        'academic_year', 'full_name', 'nisn', 'gender', 'birth_place', 'birth_date', 'previous_school', 'address',
        'parent_name', 'parent_phone', 'email', 'major_id', 'status', 'note', 'documents',
    ];

    protected static function booted(): void
    {
        static::deleted(fn (self $registrant) => Storage::disk(self::DISK)->deleteDirectory($registrant->folder()));
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /** Kode akses 8 karakter (±8,5 × 10^11 kemungkinan) untuk cek status; nomor pendaftaran saja bisa ditebak (berurutan). */
    public static function generateAccessCode(): string
    {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        return $code;
    }

    public function folder(): string
    {
        return "spmb/{$this->id}";
    }

    public function documentPath(string $type): ?string
    {
        return $this->documents[$type] ?? null;
    }

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'documents' => 'array', 'access_code' => 'encrypted'];
    }
}
