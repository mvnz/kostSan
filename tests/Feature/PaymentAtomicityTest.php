<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAtomicityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
    }

    private function lease(): Sewa
    {
        $room = Kamar::create(['nomor' => 'ATOM', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Penghuni Sintetis', 'telepon' => '']);

        return Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    }

    private function payload(Sewa $lease): array
    {
        return ['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'transfer', 'jumlah' => 550000, 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')];
    }

    public function test_failed_invoice_insert_rolls_back_manual_payment_and_upload(): void
    {
        $lease = $this->lease();
        DB::statement("CREATE TRIGGER fail_invoice BEFORE INSERT ON invoices BEGIN SELECT RAISE(ABORT, 'synthetic invoice failure'); END");
        $this->postJson('/pembayarans', $this->payload($lease))->assertStatus(500);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
    }

    public function test_failed_update_keeps_old_proof_and_removes_replacement(): void
    {
        $lease = $this->lease();
        $old = 'bukti-pembayaran-sewa/original.pdf';
        Storage::disk('local')->put($old, 'original proof');
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 550000, 'status' => 'belum_lunas', 'bukti_pembayaran_path' => $old]);
        DB::statement("CREATE TRIGGER fail_invoice BEFORE UPDATE ON invoices BEGIN SELECT RAISE(ABORT, 'synthetic invoice failure'); END");
        $payload = $this->payload($lease);
        $payload['jumlah'] = 650000;
        $this->putJson('/pembayarans/'.$payment->id, $payload)->assertStatus(500);
        Storage::disk('local')->assertExists($old);
        $this->assertSame([$old], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id, 'metode' => 'cash', 'bukti_pembayaran_path' => $old]);
    }

    public function test_successful_replacement_and_delete_clean_up_private_proof(): void
    {
        $lease = $this->lease();
        $this->post('/pembayarans', $this->payload($lease))->assertRedirect();
        $payment = Pembayaran::firstOrFail();
        $old = $payment->bukti_pembayaran_path;
        $this->put('/pembayarans/'.$payment->id, $this->payload($lease))->assertRedirect();
        Storage::disk('local')->assertMissing($old);
        $new = $payment->fresh()->bukti_pembayaran_path;
        Storage::disk('local')->assertExists($new);
        $this->delete('/pembayarans/'.$payment->id)->assertRedirect();
        Storage::disk('local')->assertMissing($new);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_failed_delete_preserves_payment_invoice_and_proof(): void
    {
        $lease = $this->lease();
        $this->post('/pembayarans', $this->payload($lease))->assertRedirect();
        $payment = Pembayaran::firstOrFail();
        DB::statement("CREATE TRIGGER fail_payment_delete BEFORE DELETE ON pembayarans BEGIN SELECT RAISE(ABORT, 'synthetic delete failure'); END");
        $this->deleteJson('/pembayarans/'.$payment->id)->assertStatus(500);
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id]);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id]);
        Storage::disk('local')->assertExists($payment->bukti_pembayaran_path);
    }

    public function test_approved_payment_rejects_upload_replacement_and_delete(): void
    {
        $lease = $this->lease();
        $this->post('/pembayarans', $this->payload($lease))->assertRedirect();
        $payment = Pembayaran::firstOrFail();
        $payment->update(['status' => 'lunas']);
        $this->putJson('/pembayarans/'.$payment->id, $this->payload($lease))->assertUnprocessable();
        $this->deleteJson('/pembayarans/'.$payment->id)->assertUnprocessable();
        $this->assertSame([$payment->bukti_pembayaran_path], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'status' => 'lunas']);
    }
}
