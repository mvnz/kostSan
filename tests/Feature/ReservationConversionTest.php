<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationConversionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kamar $room;

    private Penghuni $resident;

    private Reservasi $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->room = Kamar::create(['nomor' => 'CONVERT-1', 'tipe' => 'A', 'harga_bulanan' => 750000, 'status' => 'tersedia']);
        $this->resident = Penghuni::create(['nama' => 'Calon Penghuni Konversi', 'telepon' => '']);
        $this->reservation = Reservasi::create([
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_reservasi' => '2026-10-10',
            'rencana_masuk' => '2026-11-01',
            'rencana_keluar' => '2027-02-01',
            'uang_muka' => 100000,
            'status' => 'dikonfirmasi',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'reservasi_id' => $this->reservation->id,
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_masuk' => '2026-11-01',
            'tanggal_keluar' => '2027-02-01',
            'biaya_bulanan' => 750000,
            'uang_jaminan' => 100000,
            'status' => 'aktif',
        ], $overrides);
    }

    public function test_conversion_form_prefills_confirmed_reservation(): void
    {
        $this->get('/sewas/create?reservasi_id='.$this->reservation->id)
            ->assertOk()
            ->assertSee('Mengonversi reservasi #'.$this->reservation->id)
            ->assertSee('name="reservasi_id" value="'.$this->reservation->id.'"', false)
            ->assertSee('value="2026-11-01"', false)
            ->assertSee('value="2027-02-01"', false);
    }

    public function test_confirmed_reservation_is_converted_to_a_linked_lease_atomically(): void
    {
        $this->post('/sewas', $this->payload())->assertRedirect('/kamars/sewa')->assertSessionHasNoErrors();

        $lease = Sewa::sole();
        $this->assertSame($this->room->id, $lease->kamar_id);
        $this->assertSame($this->resident->id, $lease->penghuni_id);
        $this->assertDatabaseHas('reservasis', [
            'id' => $this->reservation->id,
            'status' => 'dikonversi',
            'sewa_id' => $lease->id,
        ]);
        $this->assertSame('terisi', $this->room->fresh()->status);
        $this->get('/reservasis/'.$this->reservation->id)->assertOk()->assertSee('Sewa #'.$lease->id);
        $this->get('/sewas/'.$lease->id)->assertOk()->assertSee('Reservasi #'.$this->reservation->id);
    }

    public function test_conversion_rejects_tampered_resident_and_keeps_reservation_open(): void
    {
        $other = Penghuni::create(['nama' => 'Penghuni Lain', 'telepon' => '']);

        $this->post('/sewas', $this->payload(['penghuni_id' => $other->id]))
            ->assertSessionHasErrors('reservasi_id');

        $this->assertDatabaseCount('sewas', 0);
        $this->assertDatabaseHas('reservasis', ['id' => $this->reservation->id, 'status' => 'dikonfirmasi', 'sewa_id' => null]);
    }

    public function test_failed_reservation_close_rolls_back_new_lease_and_room_state(): void
    {
        DB::statement("CREATE TRIGGER fail_conversion BEFORE UPDATE ON reservasis BEGIN SELECT RAISE(ABORT, 'synthetic conversion failure'); END");

        $this->postJson('/sewas', $this->payload())->assertStatus(500);

        $this->assertDatabaseCount('sewas', 0);
        $this->assertDatabaseHas('reservasis', ['id' => $this->reservation->id, 'status' => 'dikonfirmasi', 'sewa_id' => null]);
        $this->assertSame('tersedia', $this->room->fresh()->status);
    }

    public function test_same_resident_cannot_bypass_confirmed_reservation_without_conversion(): void
    {
        $payload = $this->payload();
        unset($payload['reservasi_id']);

        $this->post('/sewas', $payload)->assertSessionHasErrors('kamar_id');

        $this->assertDatabaseCount('sewas', 0);
        $this->assertSame('dikonfirmasi', $this->reservation->fresh()->status);
    }

    public function test_converted_reservation_is_immutable_and_cannot_be_converted_twice(): void
    {
        $this->post('/sewas', $this->payload())->assertRedirect('/kamars/sewa');
        $lease = Sewa::sole();

        $this->post('/sewas', $this->payload())->assertSessionHasErrors('reservasi_id');
        $this->putJson('/reservasis/'.$this->reservation->id, [])->assertUnprocessable()->assertJsonValidationErrors('reservasi');
        $this->deleteJson('/reservasis/'.$this->reservation->id)->assertUnprocessable()->assertJsonValidationErrors('reservasi');
        $this->delete('/sewas/'.$lease->id)->assertRedirect('/sewas')->assertSessionHas('error');

        $this->assertDatabaseCount('sewas', 1);
        $this->assertDatabaseHas('reservasis', ['id' => $this->reservation->id, 'status' => 'dikonversi', 'sewa_id' => $lease->id]);
    }
}
