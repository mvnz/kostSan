<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingCoverageTest extends TestCase
{
    use DatabaseMigrations;

    private function lease(): Sewa
    {
        $r = Kamar::create(['nomor' => 'COVERAGE', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $p = Penghuni::create(['nama' => 'Sintetis', 'telepon' => '']);

        return Sewa::create(['kamar_id' => $r->id, 'penghuni_id' => $p->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2027-01-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    }

    private function link(Sewa $lease): SewaPaymentLink
    {
        return SewaPaymentLink::create(['sewa_id' => $lease->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
    }

    public function test_three_month_link_payment_prevents_bulk_charges_for_every_covered_month(): void
    {
        $lease = $this->lease();
        $link = $this->link($lease);
        $this->post('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer'])->assertOk();
        $this->actingAs(User::factory()->create());
        $this->mock(WhatsAppService::class)->shouldIgnoreMissing();
        $this->post('/pembayarans/'.Pembayaran::firstOrFail()->id.'/approve')->assertRedirect();
        foreach (['2026-10', '2026-11', '2026-12'] as $month) {
            $this->post('/pembayarans-bulk', ['bulan' => $month, 'pilih_sewa' => 1, 'sewa_ids' => [$lease->id]])->assertRedirect();
        }
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('keuangans', 1);
    }

    public function test_separate_payment_link_cannot_duplicate_existing_term_or_leave_an_upload(): void
    {
        Storage::fake('local');
        $lease = $this->lease();
        $first = $this->link($lease);
        $second = $this->link($lease);
        $this->post('/pembayaran/sewa/'.$first->token, ['metode' => 'transfer'])->assertOk();
        $this->postJson('/pembayaran/sewa/'.$second->token, ['metode' => 'transfer', 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertUnprocessable()->assertJsonValidationErrors('metode');
        $this->assertNull($second->fresh()->used_at);
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
    }

    public function test_coverage_is_snapshot_and_checkout_month_can_be_billed_after_extension(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        $link = $this->link($lease);
        $this->post('/pembayaran/sewa/'.$link->token, ['metode' => 'cash', 'coverage_start' => '1900-01-01', 'coverage_end' => '9999-12-31'])->assertOk();
        $payment = Pembayaran::firstOrFail();
        $this->assertSame('2026-10-01', $payment->coverage_start->toDateString());
        $this->assertSame('2027-01-01', $payment->coverage_end->toDateString());
        $lease->update(['tanggal_keluar' => '2027-02-01']);
        $this->post('/pembayarans-bulk', ['bulan' => '2027-01', 'pilih_sewa' => 1, 'sewa_ids' => [$lease->id]])->assertRedirect();
        $this->assertDatabaseCount('pembayarans', 2);
        $this->assertSame('2027-01-01', $payment->fresh()->coverage_end->toDateString());
        $this->putJson('/pembayarans/'.$payment->id, ['sewa_id' => $lease->id, 'periode' => '2026-11-01', 'metode' => 'cash', 'jumlah' => 1])->assertUnprocessable()->assertJsonValidationErrors('periode');
    }

    public function test_existing_manual_monthly_bill_blocks_full_term_link_without_guessing_legacy_coverage(): void
    {
        $lease = $this->lease();
        $link = $this->link($lease);
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-11-20', 'metode' => 'cash', 'jumlah' => 550000, 'status' => 'belum_lunas']);
        $this->postJson('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer'])->assertUnprocessable();
        $this->assertNull($payment->fresh()->coverage_start);
        $this->assertNull($link->fresh()->used_at);
        $this->assertDatabaseCount('pembayarans', 1);
    }

    public function test_manual_monthly_bill_rejects_an_overlap_without_leaving_an_upload(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        $payload = [
            'sewa_id' => $lease->id,
            'periode' => '2026-11-20',
            'metode' => 'transfer',
            'jumlah' => 550000,
        ];

        $this->post('/pembayarans', $payload)->assertRedirect('/pembayarans');
        $this->postJson('/pembayarans', $payload + [
            'bukti_pembayaran' => UploadedFile::fake()->create('duplicate.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('periode');

        $payment = Pembayaran::sole();
        $this->assertSame('2026-11-01', $payment->coverage_start->toDateString());
        $this->assertSame('2026-12-01', $payment->coverage_end->toDateString());
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
    }

    public function test_manual_overlap_requires_and_records_an_explicit_reason(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-11-01',
            'coverage_start' => '2026-11-01',
            'coverage_end' => '2026-12-01',
            'metode' => 'cash',
            'jumlah' => 400000,
            'status' => 'belum_lunas',
        ]);
        $payload = [
            'sewa_id' => $lease->id,
            'periode' => '2026-11-15',
            'metode' => 'cash',
            'jumlah' => 150000,
            'overlap_override' => '1',
        ];

        $this->postJson('/pembayarans', $payload + ['overlap_override_reason' => 'terlalu'])->assertUnprocessable()->assertJsonValidationErrors('overlap_override_reason');
        $reason = 'Cicilan kedua sesuai kesepakatan penghuni <script>alert(1)</script>';
        $this->post('/pembayarans', $payload + ['overlap_override_reason' => $reason])->assertRedirect('/pembayarans');

        $payment = Pembayaran::latest('id')->firstOrFail();
        $this->assertSame($reason, $payment->overlap_override_reason);
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertSee('Pengecualian benturan periode')->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('pembayarans', 2);
        $this->assertDatabaseCount('invoices', 2);
    }

    public function test_manual_overlap_override_is_rejected_when_there_is_no_overlap(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();

        $this->postJson('/pembayarans', [
            'sewa_id' => $lease->id,
            'periode' => '2026-11-01',
            'metode' => 'cash',
            'jumlah' => 550000,
            'overlap_override' => '1',
            'overlap_override_reason' => 'Alasan yang sebenarnya tidak diperlukan',
        ])->assertUnprocessable()->assertJsonValidationErrors('overlap_override');

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_full_term_link_payment_also_blocks_a_manual_monthly_bill(): void
    {
        $lease = $this->lease();
        $this->post('/pembayaran/sewa/'.$this->link($lease)->token, ['metode' => 'transfer'])->assertOk();
        $this->actingAs(User::factory()->create());

        $this->postJson('/pembayarans', [
            'sewa_id' => $lease->id,
            'periode' => '2026-12-15',
            'metode' => 'cash',
            'jumlah' => 550000,
        ])->assertUnprocessable()->assertJsonValidationErrors('periode');

        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_moving_a_legacy_bill_cannot_bypass_overlap_protection(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        $existing = Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-11-01',
            'coverage_start' => '2026-11-01',
            'coverage_end' => '2026-12-01',
            'metode' => 'cash',
            'jumlah' => 400000,
            'status' => 'belum_lunas',
        ]);
        $legacy = Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-10-01',
            'metode' => 'cash',
            'jumlah' => 150000,
            'status' => 'belum_lunas',
        ]);
        $payload = [
            'sewa_id' => $lease->id,
            'periode' => '2026-11-10',
            'metode' => 'cash',
            'jumlah' => 150000,
        ];

        $this->putJson('/pembayarans/'.$legacy->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('periode');
        $this->assertSame('2026-10-01', $legacy->fresh()->periode->toDateString());

        $reason = 'Pemindahan cicilan kedua yang sudah disetujui operator';
        $this->put('/pembayarans/'.$legacy->id, $payload + [
            'overlap_override' => '1',
            'overlap_override_reason' => $reason,
        ])->assertRedirect('/pembayarans');

        $legacy->refresh();
        $this->assertSame('2026-11-01', $legacy->coverage_start->toDateString());
        $this->assertSame('2026-12-01', $legacy->coverage_end->toDateString());
        $this->assertSame($reason, $legacy->overlap_override_reason);
        $this->assertSame('2026-11-01', $existing->fresh()->coverage_start->toDateString());
    }

    public function test_invalid_lease_interval_cannot_create_payment_or_consume_token(): void
    {
        $lease = $this->lease();
        $lease->update(['tanggal_keluar' => $lease->tanggal_masuk]);
        $link = $this->link($lease);
        $this->postJson('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer'])->assertUnprocessable()->assertJsonValidationErrors('metode');
        $this->assertNull($link->fresh()->used_at);
        $this->assertDatabaseCount('pembayarans', 0);
    }
}
