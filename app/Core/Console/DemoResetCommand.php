<?php

declare(strict_types=1);

namespace App\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mengembalikan situs demo ke keadaan awal: database dikosongkan & diisi ulang, berkas unggahan dihapus.
 * Hanya berjalan bila DEMO_MODE=true (perlindungan agar tidak terpanggil di situs sungguhan).
 */
final class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset situs demo: kosongkan database dan berkas unggahan, lalu isi ulang data contoh (hanya bila DEMO_MODE=true)';

    public function handle(): int
    {
        if (! config('platform.demo.enabled')) {
            $this->error('Ditolak: DEMO_MODE tidak aktif. Perintah ini menghapus SEMUA data.');

            return self::FAILURE;
        }

        // Berkas dulu: bila database gagal diisi ulang, tidak ada berkas yatim dari data lama.
        foreach (['public', 'local'] as $disk) {
            foreach (Storage::disk($disk)->directories() as $directory) {
                Storage::disk($disk)->deleteDirectory($directory);
            }
            Storage::disk($disk)->delete(array_filter(Storage::disk($disk)->files(), fn (string $file) => $file !== '.gitignore'));
        }

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);
        $this->call('db:seed', ['--class' => config('platform.demo.seeder'), '--force' => true]);
        $this->call('cache:clear');

        $this->info('Situs demo sudah direset.');

        return self::SUCCESS;
    }
}
