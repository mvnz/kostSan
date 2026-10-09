<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaseFileAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private Kamar $room;

    private Penghuni $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        $this->room = Kamar::create(['nomor' => 'LEASE-FILE', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $this->resident = Penghuni::create(['nama' => 'Lease File Synthetic', 'telepon' => '']);
    }

    private function payload(): array
    {
        return [
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
        ];
    }

    private function lease(): Sewa
    {
        Storage::disk('local')->put('bukti-sewa/original.pdf', 'synthetic');

        return Sewa::create([
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => 'bukti-sewa/original.pdf',
        ]);
    }

    public function test_failed_lease_insert_cleans_new_proof(): void
    {
        DB::statement("CREATE TRIGGER fail_lease_file BEFORE INSERT ON sewas BEGIN SELECT RAISE(ABORT, 'synthetic'); END");

        $this->postJson('/sewas', $this->payload())->assertStatus(500);

        $this->assertDatabaseCount('sewas', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-sewa'));
        $this->assertSame('tersedia', $this->room->fresh()->status);
    }

    public function test_failed_lease_update_cleans_replacement_and_preserves_original(): void
    {
        $lease = $this->lease();
        DB::statement("CREATE TRIGGER fail_lease_file_update BEFORE UPDATE ON sewas BEGIN SELECT RAISE(ABORT, 'synthetic'); END");

        $this->putJson('/sewas/'.$lease->id, $this->payload())->assertStatus(500);

        $this->assertSame('bukti-sewa/original.pdf', $lease->fresh()->bukti_pembayaran);
        $this->assertSame(['bukti-sewa/original.pdf'], Storage::disk('local')->allFiles('bukti-sewa'));
    }

    public function test_successful_lease_delete_removes_proof_after_commit(): void
    {
        $lease = $this->lease();
        $this->room->update(['status' => 'terisi']);

        $this->delete('/sewas/'.$lease->id)->assertRedirect('/sewas');

        $this->assertDatabaseMissing('sewas', ['id' => $lease->id]);
        Storage::disk('local')->assertMissing('bukti-sewa/original.pdf');
        $this->assertSame('tersedia', $this->room->fresh()->status);
    }
}
