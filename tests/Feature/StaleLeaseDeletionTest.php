<?php

namespace Tests\Feature;

use App\Http\Controllers\SewaController;
use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StaleLeaseDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_delete_after_room_move_preserves_lease_and_proof_until_fresh_retry(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Synthetic lease deletion', 'telepon' => '']);
        $oldRoom = Kamar::create(['nomor' => 'DELETE-OLD', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $newRoom = Kamar::create(['nomor' => 'DELETE-NEW', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $proof = 'bukti-sewa/synthetic-delete.pdf';
        Storage::disk('local')->put($proof, 'Synthetic private proof');
        $lease = Sewa::create(['kamar_id' => $oldRoom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif', 'bukti_pembayaran' => $proof]);
        $bound = Sewa::findOrFail($lease->id);
        $this->putJson('/sewas/'.$lease->id, [
            'kamar_id' => $newRoom->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000, 'status' => 'aktif',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $history = $lease->fresh()->getAttributes();

        try {
            app(SewaController::class)->destroy($bound);
            $this->fail('Old deletion modified a lease after its room changed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sewa', $exception->errors());
            $this->assertStringContainsString('Muat ulang', $exception->errors()['sewa'][0]);
        }
        $this->assertSame($history, $lease->fresh()->getAttributes());
        $this->assertSame('tersedia', $oldRoom->fresh()->status);
        $this->assertSame('terisi', $newRoom->fresh()->status);
        Storage::disk('local')->assertExists($proof);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('keuangans', 0);

        $this->delete('/sewas/'.$lease->id)->assertRedirect('/sewas')->assertSessionHas('success');
        $this->assertDatabaseMissing('sewas', ['id' => $lease->id]);
        $this->assertSame('tersedia', $newRoom->fresh()->status);
        $this->assertSame('tersedia', $oldRoom->fresh()->status);
    }
}
