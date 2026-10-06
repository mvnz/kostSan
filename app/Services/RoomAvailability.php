<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\Reservasi;
use App\Models\Sewa;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RoomAvailability
{
    /** Call within a transaction after locking the room row. Checkout is exclusive. */
    public function assertAvailable(
        Kamar $room,
        string $start,
        ?string $end,
        ?int $exceptLease = null,
        ?int $exceptReservation = null,
        ?int $resident = null,
    ): void {
        $start = Carbon::parse($start)->toDateString();
        $end = $end ? Carbon::parse($end)->toDateString() : null;
        if ($room->status === 'perbaikan') {
            throw ValidationException::withMessages(['kamar_id' => 'Kamar sedang dalam perbaikan.']);
        }

        $leases = Sewa::where('kamar_id', $room->id)
            ->whereIn('status', ['aktif', 'menunggak'])
            ->when($exceptLease, fn ($q) => $q->where('id', '!=', $exceptLease))
            ->where(fn ($q) => $q->whereNull('tanggal_keluar')->orWhereDate('tanggal_keluar', '>', $start))
            ->when($end, fn ($q) => $q->whereDate('tanggal_masuk', '<', $end));

        $reservations = Reservasi::where('kamar_id', $room->id)
            ->where('status', 'dikonfirmasi')
            ->when($exceptReservation, fn ($q) => $q->where('id', '!=', $exceptReservation))
            // A confirmed reservation may be converted into this resident's lease.
            ->when($resident, fn ($q) => $q->where('penghuni_id', '!=', $resident))
            ->where(fn ($q) => $q->whereNull('rencana_keluar')->orWhereDate('rencana_keluar', '>', $start))
            ->when($end, fn ($q) => $q->whereDate('rencana_masuk', '<', $end));

        if ($leases->exists() || $reservations->exists()) {
            throw ValidationException::withMessages([
                'kamar_id' => 'Periode kamar berbenturan dengan sewa atau reservasi yang sudah dikonfirmasi. Pilih kamar atau periode lain.',
            ]);
        }
    }
}
