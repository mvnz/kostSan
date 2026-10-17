<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StalePaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_approval_after_legacy_payment_move_is_rejected_and_fresh_retry_recovers(): void
    {
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Synthetic stale approval', 'telepon' => '']);
        $oldRoom = Kamar::create(['nomor' => 'STALE-OLD', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $newRoom = Kamar::create(['nomor' => 'STALE-NEW', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $oldLease = Sewa::create(['kamar_id' => $oldRoom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-09-01', 'tanggal_keluar' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'selesai']);
        $newLease = Sewa::create(['kamar_id' => $newRoom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
        $payment = Pembayaran::create(['sewa_id' => $oldLease->id, 'periode' => '2026-09-01', 'jumlah' => 550000, 'metode' => 'cash', 'status' => 'belum_lunas']);
        // Simulate route binding before the other request commits, including cached relations.
        $bound = Pembayaran::with('sewa.kamar')->findOrFail($payment->id);
        $this->putJson('/pembayarans/'.$payment->id, [
            'sewa_id' => $newLease->id, 'periode' => '2026-10-01', 'jumlah' => 650000, 'metode' => 'cash',
        ])->assertRedirect();

        try {
            app(PembayaranController::class)->approve($bound);
            $this->fail('An old request approved a moved payment; old room status='.$oldRoom->fresh()->status);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pembayaran', $exception->errors());
            $this->assertStringContainsString('Muat ulang', $exception->errors()['pembayaran'][0]);
        }
        $this->assertSame('tersedia', $oldRoom->fresh()->status);
        $this->assertSame('selesai', $oldLease->fresh()->status);
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id, 'sewa_id' => $newLease->id, 'status' => 'belum_lunas', 'jumlah' => 650000]);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'status' => 'draft', 'jumlah_tagihan' => 650000]);
        $this->assertDatabaseCount('keuangans', 0);

        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->assertSame('tersedia', $oldRoom->fresh()->status);
        $this->assertSame('terisi', $newRoom->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('keuangans', 1);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'status' => 'lunas', 'jumlah_tagihan' => 650000]);
        $this->assertDatabaseHas('keuangans', ['payment_id' => $payment->id, 'jumlah' => 650000]);
    }
}
