<?php

namespace App\Services;

use App\Models\Sewa;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class BillingCoverage
{
    public function overlaps(Sewa $lease, CarbonInterface $start, CarbonInterface $end, ?int $exceptPaymentId = null): bool
    {
        $payments = $lease->pembayarans();
        if ($exceptPaymentId !== null) {
            $payments->whereKeyNot($exceptPaymentId);
        }

        return $payments->where(function (Builder $payments) use ($start, $end): void {
            $payments->where(fn (Builder $explicit) => $explicit->whereNotNull('coverage_start')->whereNotNull('coverage_end')
                ->whereDate('coverage_start', '<', $end->toDateString())->whereDate('coverage_end', '>', $start->toDateString()))
                ->orWhere(fn (Builder $legacy) => $legacy->whereNull('coverage_start')
                    ->where('periode', '>=', $start->copy()->startOfMonth()->toDateString())->where('periode', '<', $end->toDateString()));
        })->exists();
    }
}
