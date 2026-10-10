<?php

namespace App\Console\Commands;

use App\Models\Reservasi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservationsCommand extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Tandai reservasi terkonfirmasi yang masa rencana tinggalnya sudah berakhir';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();
        $expired = 0;

        Reservasi::query()
            ->where('status', 'dikonfirmasi')
            ->whereNull('sewa_id')
            ->whereNotNull('rencana_keluar')
            ->whereDate('rencana_keluar', '<=', $today)
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$expired, $today): void {
                foreach ($reservations as $reservation) {
                    $changed = DB::transaction(function () use ($reservation, $today): bool {
                        $locked = Reservasi::query()
                            ->whereKey($reservation->id)
                            ->where('status', 'dikonfirmasi')
                            ->whereNull('sewa_id')
                            ->whereNotNull('rencana_keluar')
                            ->whereDate('rencana_keluar', '<=', $today)
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null) {
                            return false;
                        }

                        $locked->update(['status' => 'kedaluwarsa']);

                        return true;
                    });

                    if ($changed) {
                        $expired++;
                    }
                }
            });

        $this->info("Reservasi kedaluwarsa: {$expired}");

        return self::SUCCESS;
    }
}
