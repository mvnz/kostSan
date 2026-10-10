<?php

namespace App\Console\Commands;

use App\Models\FileCleanupJob;
use App\Services\PrivateFileCleanup;
use Illuminate\Console\Command;

class RetryPrivateFileCleanupCommand extends Command
{
    protected $signature = 'private-files:cleanup {--limit=100 : Maksimum antrean yang dicoba} {--dry-run : Tampilkan antrean tanpa menghapus file}';

    protected $description = 'Coba ulang penghapusan file privat yang gagal dan tersimpan dalam antrean';

    public function handle(PrivateFileCleanup $cleanup): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $jobs = FileCleanupJob::orderBy('id')->limit($limit)->get();
        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Konteks', 'Percobaan', 'Percobaan terakhir', 'Path'],
                $jobs->map(fn (FileCleanupJob $job): array => [$job->id, $job->context, $job->attempts, $job->last_attempt_at?->format('Y-m-d H:i:s') ?? '-', $job->path])->all()
            );
            $this->info(sprintf('Antrean tertunda: %d; ditampilkan: %d.', FileCleanupJob::count(), $jobs->count()));

            return self::SUCCESS;
        }
        $deleted = 0;

        foreach ($jobs as $job) {
            if ($cleanup->deleteOrQueue($job->path, $job->context)) {
                $deleted++;
            }
        }

        $this->info(sprintf('Pembersihan file berhasil: %d; gagal run ini: %d; tersisa: %d.', $deleted, $jobs->count() - $deleted, FileCleanupJob::count()));

        return self::SUCCESS;
    }
}
