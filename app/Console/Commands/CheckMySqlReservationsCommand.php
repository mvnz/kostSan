<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckMySqlReservationsCommand extends Command
{
    protected $signature = 'kost:check-mysql-reservations';

    protected $description = 'Periksa kompatibilitas enum reservasi MySQL secara read-only';

    public function handle(): int
    {
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() !== 'mysql') {
                $this->error('Pemeriksaan ini memerlukan koneksi MySQL; SQLite bukan bukti kompatibilitas enum MySQL.');
                $this->line('Perintah khusus MySQL ini tidak membatasi penggunaan SQLite oleh aplikasi.');

                return self::FAILURE;
            }

            $columns = $connection->getSchemaBuilder()->getColumns('reservasis');
        } catch (Throwable) {
            $this->error('Tidak dapat membaca metadata reservasi. Periksa koneksi dan izin metadata di lingkungan tujuan, lalu ulangi.');

            return self::FAILURE;
        }

        $column = collect($columns)->firstWhere('name', 'status');
        if ($column === null) {
            $this->error('Kolom reservasis.status tidak ditemukan. Tinjau status migrasi sebelum melanjutkan deployment.');

            return self::FAILURE;
        }

        $type = $column['type'];
        if ($column['type_name'] !== 'enum' || ! str_starts_with($type, 'enum(') || ! str_ends_with($type, ')')) {
            $this->error('Kolom reservasis.status bukan enum MySQL. Tinjau penyimpangan skema sebelum deployment.');

            return self::FAILURE;
        }

        $members = str_getcsv(substr($type, 5, -1), ',', "'", '\\');
        $missing = array_diff(['menunggu', 'dikonfirmasi', 'dibatalkan', 'dikonversi', 'kedaluwarsa'], $members);
        if ($missing !== []) {
            $this->error('Enum reservasi belum mendukung status: '.implode(', ', $missing).'.');
            $this->line('Tinjau migrasi 2026_10_19_090000_expand_reservation_lifecycle_statuses, backup, dan dampak DDL di staging.');
            $this->line('Perintah ini tidak menjalankan migrasi atau mengubah data.');

            return self::FAILURE;
        }

        $this->info('Enum reservasi MySQL mendukung kelima status lifecycle.');
        $this->line('Hanya metadata enum; bukan bukti migrasi lengkap, integritas data, transaksi, atau kesiapan produksi.');

        return self::SUCCESS;
    }
}
