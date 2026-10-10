<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 00:05:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_expires_only_finished_unconverted_confirmed_reservations(): void
    {
        $yesterday = $this->reservation('EXP-YESTERDAY', 'dikonfirmasi', '2026-10-09');
        $today = $this->reservation('EXP-TODAY', 'dikonfirmasi', '2026-10-10');
        $future = $this->reservation('EXP-FUTURE', 'dikonfirmasi', '2026-10-11');
        $openEnded = $this->reservation('EXP-OPEN', 'dikonfirmasi', null);
        $waiting = $this->reservation('EXP-WAIT', 'menunggu', '2026-10-09');
        $converted = $this->reservation('EXP-CONVERTED', 'dikonversi', '2026-10-09');
        $lease = Sewa::create([
            'kamar_id' => $converted->kamar_id,
            'penghuni_id' => $converted->penghuni_id,
            'tanggal_masuk' => '2026-09-01',
            'tanggal_keluar' => '2026-10-09',
            'biaya_bulanan' => 550000,
            'status' => 'selesai',
        ]);
        $converted->update(['sewa_id' => $lease->id]);

        $this->artisan('reservations:expire')
            ->expectsOutput('Reservasi kedaluwarsa: 2')
            ->assertSuccessful();

        $this->assertSame('kedaluwarsa', $yesterday->fresh()->status);
        $this->assertSame('kedaluwarsa', $today->fresh()->status);
        $this->assertSame('dikonfirmasi', $future->fresh()->status);
        $this->assertSame('dikonfirmasi', $openEnded->fresh()->status);
        $this->assertSame('menunggu', $waiting->fresh()->status);
        $this->assertSame('dikonversi', $converted->fresh()->status);

        $this->artisan('reservations:expire')
            ->expectsOutput('Reservasi kedaluwarsa: 0')
            ->assertSuccessful();
    }

    public function test_checkout_date_is_exclusive_for_active_reservation_indicator(): void
    {
        $endingToday = $this->reservation('EXP-INDICATOR-TODAY', 'dikonfirmasi', '2026-10-10');
        $endingTomorrow = $this->reservation('EXP-INDICATOR-TOMORROW', 'dikonfirmasi', '2026-10-11');

        $this->assertSame(0, $endingToday->kamar->confirmedReservations()->count());
        $this->assertSame(1, $endingTomorrow->kamar->confirmedReservations()->count());
    }

    private function reservation(string $roomNumber, string $status, ?string $end): Reservasi
    {
        $room = Kamar::create(['nomor' => $roomNumber, 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $resident = Penghuni::create(['nama' => 'Penghuni '.$roomNumber, 'telepon' => '']);

        return Reservasi::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-09-01',
            'rencana_masuk' => '2026-09-01',
            'rencana_keluar' => $end,
            'status' => $status,
        ]);
    }
}
