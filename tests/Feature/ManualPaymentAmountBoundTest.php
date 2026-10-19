<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentAmountBoundTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        $room = Kamar::create(['nomor' => 'AMOUNT', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Synthetic amount boundary', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return ['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'jumlah' => '550000.25', 'metode' => 'cash'];
    }

    public function test_invalid_create_rejects_before_payment_invoice_or_private_upload(): void
    {
        $payload = $this->payload();
        foreach (['10000000000.00', '1e20', '-0.01', 'not-a-number'] as $amount) {
            $this->postJson('/pembayarans', array_replace($payload, ['jumlah' => $amount,
                'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]))
                ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
            $this->assertDatabaseCount('pembayarans', 0);
            $this->assertDatabaseCount('invoices', 0);
            $this->assertDatabaseCount('keuangans', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_invalid_update_preserves_original_payment_invoice_and_proof_then_recovers(): void
    {
        $payload = $this->payload();
        $old = 'bukti-pembayaran-sewa/original.pdf';
        Storage::disk('local')->put($old, 'Synthetic original proof');
        $payment = Pembayaran::create($payload + ['status' => 'belum_lunas', 'bukti_pembayaran_path' => $old]);
        $history = $payment->getAttributes();
        $invoice = $payment->invoice->getAttributes();
        foreach (['10000000000.00', '1e20', '-0.01', 'not-a-number'] as $amount) {
            $this->putJson('/pembayarans/'.$payment->id, array_replace($payload, ['jumlah' => $amount,
                'bukti_pembayaran' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')]))
                ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
            $this->assertSame($history, $payment->fresh()->getAttributes());
            $this->assertSame($invoice, $payment->invoice->fresh()->getAttributes());
            $this->assertSame([$old], Storage::disk('local')->allFiles());
            $this->assertDatabaseCount('keuangans', 0);
            $this->assertDatabaseCount('file_cleanup_jobs', 0);
        }
        $this->putJson('/pembayarans/'.$payment->id, array_replace($payload, ['jumlah' => '650000.25']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('650000.25', $payment->fresh()->jumlah);
        $this->assertSame('650000.25', $payment->invoice->fresh()->jumlah_tagihan);
        Storage::disk('local')->assertExists($old);
    }

    public function test_zero_and_storage_maximum_remain_valid_with_invoice_and_idempotent_approval(): void
    {
        $payload = $this->payload();
        $this->post('/pembayarans', array_replace($payload, ['jumlah' => '0']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $payment = Pembayaran::sole();
        $this->assertSame('0.00', $payment->jumlah);
        $this->assertSame('0.00', $payment->invoice->jumlah_tagihan);
        $this->put('/pembayarans/'.$payment->id, array_replace($payload, ['jumlah' => '9999999999.99']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('9999999999.99', $payment->fresh()->jumlah);
        $this->assertSame('9999999999.99', $payment->invoice->fresh()->jumlah_tagihan);
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('keuangans', 1);
        $this->assertSame('9999999999.99', $payment->fresh()->ledgerEntry->jumlah);
    }
}
