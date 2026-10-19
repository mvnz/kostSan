<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicPaymentTotalBoundTest extends TestCase
{
    use RefreshDatabase;

    private function link(string $rate, string $end): SewaPaymentLink
    {
        Storage::fake('local');
        $room = Kamar::create(['nomor' => 'PUBLIC-TOTAL', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Synthetic public total', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => $end, 'biaya_bulanan' => $rate, 'status' => 'aktif']);

        return SewaPaymentLink::create(['sewa_id' => $lease->id, 'token' => Str::random(64), 'expires_at' => now()->addDay()]);
    }

    private function payload(): array
    {
        return ['metode' => 'transfer', 'jumlah' => '1', 'coverage_start' => '1900-01-01',
            'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')];
    }

    private function assertOverflowUnchanged(SewaPaymentLink $link): void
    {
        $history = [$link->fresh()->getAttributes(), $link->sewa->fresh()->getAttributes(), $link->sewa->kamar->fresh()->getAttributes()];
        $this->postJson('/pembayaran/sewa/'.$link->token, $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('metode');
        $this->assertSame($history, [$link->fresh()->getAttributes(), $link->sewa->fresh()->getAttributes(), $link->sewa->kamar->fresh()->getAttributes()]);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('keuangans', 0);
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_multiplied_overflow_preserves_link_and_proof_then_operator_correction_allows_one_payment(): void
    {
        $link = $this->link('5000000000.00', '2027-01-01');
        $this->assertOverflowUnchanged($link);
        $link->sewa->update(['biaya_bulanan' => '550000.25']);
        $this->post('/pembayaran/sewa/'.$link->token, $this->payload())->assertOk()->assertViewIs('sewa-payment-registrations.success');
        $payment = Pembayaran::sole();
        $this->assertSame('1650000.00', $payment->jumlah);
        $this->assertSame('1650000.00', $payment->invoice->jumlah_tagihan);
        $this->assertSame('2026-10-01', $payment->coverage_start->toDateString());
        $this->assertSame('2027-01-01', $payment->coverage_end->toDateString());
        $this->assertNotNull($link->fresh()->used_at);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->post('/pembayaran/sewa/'.$link->token, $this->payload())->assertViewIs('sewa-payment-registrations.expired');
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->actingAs(User::factory()->create());
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('keuangans', 1);
        $this->assertSame('1650000.00', $payment->fresh()->ledgerEntry->jumlah);
    }

    public function test_rounding_overflow_is_rejected_but_largest_valid_rounded_total_is_not_clamped(): void
    {
        $link = $this->link('9999999999.99', '2026-11-01');
        $this->assertOverflowUnchanged($link);
        $link->sewa->update(['biaya_bulanan' => '9999950000.00']);
        $this->post('/pembayaran/sewa/'.$link->token, $this->payload())->assertOk()->assertViewIs('sewa-payment-registrations.success');
        $payment = Pembayaran::sole();
        $this->assertSame('9999950000.00', $payment->jumlah);
        $this->assertSame('9999950000.00', $payment->invoice->jumlah_tagihan);
    }

    public function test_existing_bill_proof_keeps_exact_amount_instead_of_recalculating_overflowing_lease(): void
    {
        $link = $this->link('5000000000.00', '2027-01-01');
        $payment = Pembayaran::create(['sewa_id' => $link->sewa_id, 'periode' => '2026-10-01',
            'jumlah' => '9999999999.99', 'metode' => 'cash', 'status' => 'belum_lunas']);
        $link->update(['payment_id' => $payment->id]);
        $this->post('/pembayaran/sewa/'.$link->token, $this->payload())->assertOk()->assertViewIs('sewa-payment-registrations.success');
        $this->assertSame('9999999999.99', $payment->fresh()->jumlah);
        $this->assertSame('9999999999.99', $payment->invoice->fresh()->jumlah_tagihan);
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('keuangans', 0);
        $this->assertNotNull($link->fresh()->used_at);
    }
}
