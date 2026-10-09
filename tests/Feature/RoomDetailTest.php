<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomDetailTest extends TestCase
{
    use RefreshDatabase;

    private Kamar $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Kamar::create([
            'nomor' => 'ROOM-DETAIL',
            'tipe' => 'A',
            'harga_bulanan' => 550000,
            'status' => 'tersedia',
            'layout_floor' => 2,
            'layout_row' => 1,
            'layout_col' => 3,
            'deskripsi' => '<script>alert(1)</script> synthetic',
        ]);
    }

    public function test_view_only_operator_can_open_room_detail_without_mutation_actions(): void
    {
        $role = Role::create(['name' => 'Room viewer', 'menu_permissions' => ['master_data.data_kamar' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/kamars/'.$this->room->id)
            ->assertOk()
            ->assertSee('ROOM-DETAIL')
            ->assertSee('550.000')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('/kamars/'.$this->room->id.'/edit', false)
            ->assertDontSee('action="'.route('kamars.destroy', $this->room).'"', false);
        $this->getJson('/kamars/'.$this->room->id.'/edit')->assertForbidden();
        $this->deleteJson('/kamars/'.$this->room->id)->assertForbidden();
    }

    public function test_room_with_reservation_history_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Room History Synthetic', 'telepon' => '080000000000']);
        $reservation = Reservasi::create([
            'kamar_id' => $this->room->id,
            'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-10-10',
            'rencana_masuk' => '2026-11-01',
            'status' => 'menunggu',
        ]);

        $this->get('/kamars/'.$this->room->id)
            ->assertOk()
            ->assertSee('Riwayat sewa/reservasi tersimpan')
            ->assertDontSee('action="'.route('kamars.destroy', $this->room).'"', false);
        $this->delete('/kamars/'.$this->room->id)->assertRedirect('/kamars')->assertSessionHas('error');
        $this->assertDatabaseHas('kamars', ['id' => $this->room->id]);
        $this->assertDatabaseHas('reservasis', ['id' => $reservation->id]);
    }

    public function test_room_without_history_can_still_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $this->delete('/kamars/'.$this->room->id)->assertRedirect('/kamars')->assertSessionHas('success');

        $this->assertDatabaseMissing('kamars', ['id' => $this->room->id]);
    }
}
