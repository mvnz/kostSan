<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Existing users are preserved. Never provision a publicly known password.
        $this->command?->info('Buat admin melalui: php artisan kost:admin-create email-anda --name="Nama Anda"');
    }
}
