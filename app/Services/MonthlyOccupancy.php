<?php

namespace App\Services;

use App\Models\Sewa;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class MonthlyOccupancy
{
    public function rooms(CarbonInterface $month, bool $includeCompleted = false): int
    {
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        return Sewa::whereIn('status', $includeCompleted ? ['aktif', 'menunggak', 'selesai'] : ['aktif', 'menunggak'])
            ->whereDate('tanggal_masuk', '<', $end->toDateString())
            ->where(fn (Builder $leases) => $leases->whereNull('tanggal_keluar')
                ->orWhereDate('tanggal_keluar', '>', $start->toDateString()))
            // Completed leases without a checkout date cannot establish historical occupancy.
            ->where(fn (Builder $leases) => $leases->whereIn('status', ['aktif', 'menunggak'])->orWhereNotNull('tanggal_keluar'))
            ->where(fn (Builder $leases) => $leases->whereNull('tanggal_keluar')
                ->orWhereColumn('tanggal_keluar', '>', 'tanggal_masuk'))
            ->distinct()->count('kamar_id');
    }
}
