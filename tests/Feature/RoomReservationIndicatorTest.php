<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\Sewa;
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
                'master_data.data_kamar' => ['view'],
                'keuangan.laporan_hunian' => ['view'],
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

    public function test_master_room_and_occupancy_report_show_confirmed_reservation_separately(): void
    {
        $room = $this->room('INDICATOR-REPORT');
        $reservation = $this->reservation($room, 'dikonfirmasi', '2026-11-01', '2026-12-01');

        $this->get('/kamars')
            ->assertOk()
            ->assertSee('INDICATOR-REPORT')
            ->assertSee('1 Reservasi')
            ->assertSee('Tersedia');

        $this->get('/laporan-hunian?tahun=2026')
            ->assertOk()
            ->assertSee('INDICATOR-REPORT')
            ->assertSee('Reservasi')
            ->assertSee($reservation->penghuni->nama)
            ->assertSee('01/11/2026')
            ->assertSee('01/12/2026');
    }

    public function test_room_map_uses_data_and_text_nodes_for_untrusted_room_and_resident_values(): void
    {
        $room = $this->room("X');alert(1);//");
        $room->update(['tipe' => "A');alert(2);//", 'status' => 'terisi']);
        $resident = Penghuni::create([
            'nama' => '</td><script>window.__roomXss=1</script>',
            'telepon' => '080000000001',
        ]);
        Sewa::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
        ]);

        $this->get('/kamars/sewa')
            ->assertOk()
            ->assertSee('onclick="openKamar(this)"', false)
            ->assertDontSee('openKamar('.$room->id, false)
            ->assertDontSee('<script>window.__roomXss', false)
            ->assertSee('data-nomor="X&#039;);alert(1);//"', false)
            ->assertSee('tbody.replaceChildren()', false)
            ->assertSee("cell.textContent = String(value ?? '-')", false);
    }

    public function test_view_only_room_operator_does_not_receive_mutation_controls(): void
    {
        $this->room('VIEW-ONLY-CONTROLS');

        $this->get('/kamars/sewa')
            ->assertOk()
            ->assertDontSee('id="btn-layout"', false)
            ->assertDontSee('id="btn-sewa-kamar"', false)
            ->assertDontSee('id="form-generate-link"', false)
            ->assertDontSee('id="form-selesai"', false)
            ->assertDontSee('id="btn-perpanjang"', false)
            ->assertSee('const canEditLease = false;', false);
    }

    public function test_room_operations_update_permission_authorizes_finish_endpoint(): void
    {
        $room = $this->room('UPDATE-PERMISSION');
        $room->update(['status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Operator Permission Synthetic', 'telepon' => '080000000002']);
        $lease = Sewa::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
        ]);
        $role = Role::create([
            'name' => 'Room operations updater',
            'menu_permissions' => ['manajemen_sewa.sewa_kamar' => ['view', 'update']],
        ]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->post('/kamars/'.$room->id.'/selesai-sewa')->assertRedirect('/kamars/sewa');
        $this->assertSame('selesai', $lease->fresh()->status);
        $this->assertSame('tersedia', $room->fresh()->status);
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
