<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReservationIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 02:30:00');
        $role = Role::create([
            'name' => 'Room operations viewer',
            'menu_permissions' => [
                'manajemen_sewa.data_sewa' => ['view'],
                'manajemen_sewa.sewa_kamar' => ['view'],
            ],
        ]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_confirmed_current_or_future_reservation_is_shown_without_changing_physical_room_status(): void
    {
        $room = $this->room('INDICATOR-CONFIRMED');
        $this->reservation($room, 'dikonfirmasi', '2026-11-01', '2026-12-01');

        $this->get('/kamars/sewa')
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts) => $counts['reservasi'] === 1)
            ->assertSee('Ada reservasi terkonfirmasi')
            ->assertSee('reservation-badge', false)
            ->assertSee('seat-tersedia', false)
            ->assertDontSee('seat-reservasi', false);

        $this->assertDatabaseHas('kamars', ['id' => $room->id, 'status' => 'tersedia']);
    }

    public function test_expired_cancelled_and_waiting_reservations_are_not_counted(): void
    {
        $expired = $this->room('INDICATOR-EXPIRED');
        $this->reservation($expired, 'dikonfirmasi', '2026-08-01', '2026-09-01');
        $cancelled = $this->room('INDICATOR-CANCELLED');
        $this->reservation($cancelled, 'dibatalkan', '2026-11-01', '2026-12-01');
        $waiting = $this->room('INDICATOR-WAITING');
        $this->reservation($waiting, 'menunggu', '2026-11-01', null);

        $response = $this->get('/kamars/sewa')
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts) => $counts['reservasi'] === 0);
        $this->assertStringNotContainsString('<span class="reservation-badge"', $response->getContent());
    }

    private function room(string $number): Kamar
    {
        return Kamar::create([
            'nomor' => $number,
            'tipe' => 'A',
            'harga_bulanan' => 550000,
            'status' => 'tersedia',
        ]);
    }

    private function reservation(Kamar $room, string $status, string $start, ?string $end): Reservasi
    {
        $resident = Penghuni::create([
            'nama' => 'Penghuni Sintetis '.$room->nomor,
            'telepon' => '080000000000',
        ]);

        return Reservasi::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-10-01',
            'rencana_masuk' => $start,
            'rencana_keluar' => $end,
            'status' => $status,
        ]);
    }
}
