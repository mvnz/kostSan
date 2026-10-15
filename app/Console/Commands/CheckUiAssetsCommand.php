<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUiAssetsCommand extends Command
{
    protected $signature = 'kost:check-ui';

    protected $description = 'Periksa kelengkapan berkas build UI tanpa mengubah data atau konfigurasi';

    public function handle(): int
    {
        $invalid = [];
        foreach (['ui/helpers.js', 'ui/core.js', 'ui/LICENSES.txt'] as $relativePath) {
            $path = public_path($relativePath);
            if (! is_file($path) || ! is_readable($path) || filesize($path) === 0) {
                $invalid[] = $relativePath;
            }
        }

        if ($invalid !== []) {
            $this->error('Paket UI belum lengkap. Berkas hilang, kosong, atau tidak terbaca:');
            foreach ($invalid as $relativePath) {
                $this->line(' - '.$relativePath);
            }
            $this->line('Bangun di lingkungan build: npm ci --ignore-scripts && npm run build');
            $this->line('Sertakan folder public/ui hasil build dalam paket deployment, lalu ulangi pemeriksaan.');

            return self::FAILURE;
        }

        $this->info('Paket UI lengkap: 3 berkas tersedia, terbaca, dan tidak kosong.');
        $this->line('Ini hanya pemeriksaan berkas; tetap jalankan smoke browser dan validasi deployment.');

        return self::SUCCESS;
    }
}
