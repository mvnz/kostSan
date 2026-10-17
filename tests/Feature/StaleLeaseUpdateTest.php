<?php

namespace Tests\Feature;

use App\Http\Controllers\SewaController;
use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StaleLeaseUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function setupMove(): array
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Synthetic concurrent lease edit', 'telepon' => '']);
        $rooms = collect(['A', 'B', 'C'])->map(fn ($name) => Kamar::create([
            'nomor' => 'UPDATE-'.$name, 'tipe' => 'Test', 'harga_bulanan' => 550000,
            'status' => $name === 'A' ? 'terisi' : 'tersedia',
        ]));
        Storage::disk('local')->put('bukti-sewa/original.pdf', 'Synthetic private proof');
        $payload = ['kamar_id' => $rooms[0]->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000, 'status' => 'aktif'];
        $lease = Sewa::create($payload + ['bukti_pembayaran' => 'bukti-sewa/original.pdf']);
        $bound = Sewa::findOrFail($lease->id);
        $this->putJson('/sewas/'.$lease->id, array_replace($payload, ['kamar_id' => $rooms[1]->id]))
            ->assertRedirect()->assertSessionHasNoErrors();

        return [$lease, $bound, $rooms, $payload];
    }

    public function test_update_rejects_a_source_room_outside_its_locks_before_storing_replacement_then_fresh_retry_works(): void
    {
        [$lease, $bound, $rooms, $payload] = $this->setupMove();
        $history = $lease->fresh()->getAttributes();
        $request = Request::create('/sewas/'.$lease->id, 'PUT', array_replace($payload, ['kamar_id' => $rooms[2]->id, 'biaya_bulanan' => 650000]), [], [
            'bukti_pembayaran' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
        ]);
        try {
            app(SewaController::class)->update($request, $bound);
            $this->fail('Old update moved the lease through an unlocked source room.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sewa', $exception->errors());
            $this->assertStringContainsString('Muat ulang', $exception->errors()['sewa'][0]);
        }
        $this->assertSame($history, $lease->fresh()->getAttributes());
        $this->assertSame(['tersedia', 'terisi', 'tersedia'], $rooms->map(fn ($room) => $room->fresh()->status)->all());
        $this->assertSame(['bukti-sewa/original.pdf'], Storage::disk('local')->allFiles('bukti-sewa'));
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('keuangans', 0);

        $this->putJson('/sewas/'.$lease->id, array_replace($payload, ['kamar_id' => $rooms[2]->id, 'biaya_bulanan' => 650000]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($rooms[2]->id, $lease->fresh()->kamar_id);
        $this->assertSame('650000.00', $lease->fresh()->biaya_bulanan);
        $this->assertSame(['tersedia', 'tersedia', 'terisi'], $rooms->map(fn ($room) => $room->fresh()->status)->all());
        Storage::disk('local')->assertExists('bukti-sewa/original.pdf');
    }

    public function test_old_binding_is_safe_when_actual_room_is_already_the_locked_destination(): void
    {
        [$lease, $bound, $rooms, $payload] = $this->setupMove();
        app(SewaController::class)->update(Request::create('/sewas/'.$lease->id, 'PUT', array_replace($payload, [
            'kamar_id' => $rooms[1]->id, 'biaya_bulanan' => 650000,
        ])), $bound);

        $this->assertSame($rooms[1]->id, $lease->fresh()->kamar_id);
        $this->assertSame('650000.00', $lease->fresh()->biaya_bulanan);
        $this->assertSame(['tersedia', 'terisi', 'tersedia'], $rooms->map(fn ($room) => $room->fresh()->status)->all());
        Storage::disk('local')->assertExists('bukti-sewa/original.pdf');
    }
}
