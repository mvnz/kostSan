<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReservationMetricTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_counts_only_current_waiting_or_confirmed_reservations(): void
    {
        $this->reservation('DASH-CONFIRMED', 'dikonfirmasi', '2026-10-11');
        $this->reservation('DASH-WAITING', 'menunggu', null);
        $this->reservation('DASH-CANCELLED', 'dibatalkan', '2026-10-11');
        $this->reservation('DASH-CONVERTED', 'dikonversi', '2026-10-11');
        $this->reservation('DASH-EXPIRED', 'kedaluwarsa', '2026-10-09');
        $this->reservation('DASH-STALE', 'dikonfirmasi', '2026-10-10');

        $this->get('/')
            ->assertOk()
            ->assertViewHas('stat', fn (array $stat) => $stat['reservasi_aktif'] === 2 && ! array_key_exists('total_reservasi', $stat))
            ->assertSee('Reservasi Aktif')
            ->assertSee('Menunggu / dikonfirmasi');
    }

    public function test_maintenance_rooms_are_not_counted_as_occupied(): void
    {
        Kamar::create(['nomor' => 'DASH-AVAILABLE', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        Kamar::create(['nomor' => 'DASH-OCCUPIED', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        Kamar::create(['nomor' => 'DASH-MAINTENANCE', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'perbaikan']);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('stat', fn (array $stat) => $stat['total_kamar'] === 3
                && $stat['kamar_tersedia'] === 1
                && $stat['kamar_terisi'] === 1);
    }

    private function reservation(string $roomNumber, string $status, ?string $end): Reservasi
    {
        $room = Kamar::create(['nomor' => $roomNumber, 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $resident = Penghuni::create(['nama' => 'Penghuni '.$roomNumber, 'telepon' => '']);

        return Reservasi::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-10-01',
            'rencana_masuk' => '2026-10-05',
            'rencana_keluar' => $end,
            'status' => $status,
        ]);
    }
}
