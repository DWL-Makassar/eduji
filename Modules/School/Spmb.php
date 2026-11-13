<?php

declare(strict_types=1);

namespace Modules\School;

use App\Core\Settings\SettingsStore;

/** Pengaturan SPMB (SettingsStore grup "school"): buka/tutup, tahun ajaran, dan informasi pendaftaran. */
final class Spmb
{
    public function __construct(private readonly SettingsStore $settings) {}

    public function isOpen(): bool
    {
        return (bool) $this->settings->get('school', 'spmb_open', false);
    }

    public function academicYear(): ?string
    {
        return $this->settings->get('school', 'spmb_year') ?: null;
    }

    public function info(): ?string
    {
        return $this->settings->get('school', 'spmb_info') ?: null;
    }

    /** @param array{spmb_open: bool, spmb_year: ?string, spmb_info: ?string} $values */
    public function save(array $values): void
    {
        $this->settings->setMany('school', $values);
    }

    /** Awalan nomor pendaftaran: tahun pertama dari tahun ajaran ("2026/2027" → SPMB-2026), atau tahun berjalan. */
    public function numberPrefix(): string
    {
        $year = preg_match('/\d{4}/', (string) $this->academicYear(), $m) ? $m[0] : now()->format('Y');

        return "SPMB-{$year}";
    }
}
