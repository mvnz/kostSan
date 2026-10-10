<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationDetailTest extends TestCase
{
    use RefreshDatabase;

    private Reservasi $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $room = Kamar::create(['nomor' => 'RES-DETAIL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $resident = Penghuni::create(['nama' => '<script>alert(1)</script> Reservation', 'telepon' => '080000000000']);
        $this->reservation = Reservasi::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-10-10',
            'rencana_masuk' => '2026-11-01',
            'rencana_keluar' => '2026-12-01',
            'uang_muka' => 100000,
            'status' => 'dikonfirmasi',
            'catatan' => '<script>alert(2)</script> synthetic',
        ]);
    }

    public function test_view_only_operator_can_open_reservation_detail_without_mutation_actions(): void
    {
        $role = Role::create(['name' => 'Reservation viewer', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/reservasis/'.$this->reservation->id)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('RES-DETAIL')
            ->assertSee('100.000')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('/reservasis/'.$this->reservation->id.'/edit', false)
            ->assertDontSee('action="'.route('reservasis.destroy', $this->reservation).'"', false);
        $this->getJson('/reservasis/'.$this->reservation->id.'/edit')->assertForbidden();
        $this->deleteJson('/reservasis/'.$this->reservation->id)->assertForbidden();
    }

    public function test_operator_without_reservation_view_permission_cannot_open_detail(): void
    {
        $role = Role::create(['name' => 'No reservation access', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/reservasis/'.$this->reservation->id)->assertRedirect('/');
    }
}
