<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    private function lease(array $attributes = []): Sewa
    {
        $room = Kamar::create(['nomor' => Str::random(8), 'tipe' => 'A', 'harga_bulanan' => 1000000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Penghuni Sintetis', 'telepon' => '']);

        return Sewa::create(array_merge([
            'kamar_id' => $room->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 1000000, 'status' => 'aktif',
        ], $attributes));
    }

    public function test_invalid_month_is_rejected_without_writing(): void
    {
        $this->lease();
        foreach (['2026-99', '2026-2', 'invalid'] as $month) {
            $this->getJson('/pembayarans-bulk?bulan='.$month)->assertUnprocessable()->assertJsonValidationErrors('bulan');
            $this->postJson('/pembayarans-bulk', ['bulan' => $month])->assertUnprocessable()->assertJsonValidationErrors('bulan');
        }
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_month_parsing_does_not_overflow_at_end_of_month(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-10-31 10:00:00'));
        $lease = $this->lease(['tanggal_masuk' => '2026-02-01', 'tanggal_keluar' => null]);
        $this->post('/pembayarans-bulk', ['bulan' => '2026-02'])->assertRedirect('/pembayarans');
        $this->assertDatabaseHas('pembayarans', ['sewa_id' => $lease->id, 'periode' => '2026-02-01 00:00:00']);
    }

    public function test_preview_and_creation_only_include_leases_overlapping_month(): void
    {
        $eligible = $this->lease(['tanggal_masuk' => '2026-10-31', 'tanggal_keluar' => null]);
        $this->lease(['tanggal_masuk' => '2026-11-01', 'tanggal_keluar' => '2026-12-01']);
        $this->lease(['tanggal_masuk' => '2026-09-01', 'tanggal_keluar' => '2026-10-01']);
        $this->lease(['status' => 'selesai']);
        $this->get('/pembayarans-bulk?bulan=2026-10')->assertOk()
            ->assertViewHas('sewas', fn ($rows) => $rows->pluck('sewa.id')->all() === [$eligible->id]);
        $this->post('/pembayarans-bulk', ['bulan' => '2026-10'])->assertRedirect();
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseHas('pembayarans', ['sewa_id' => $eligible->id, 'jumlah' => 1000000]);
    }

    public function test_selected_billing_only_creates_selected_leases_and_retry_skips_existing(): void
    {
        $chosen = $this->lease();
        $other = $this->lease();
        $payload = ['bulan' => '2026-10', 'pilih_sewa' => 1, 'sewa_ids' => [$chosen->id]];
        $this->get('/pembayarans-bulk?bulan=2026-10')->assertOk()->assertSee('sewa_ids[]', false);
        $this->post('/pembayarans-bulk', $payload)->assertRedirect();
        $this->post('/pembayarans-bulk', $payload)->assertRedirect();
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseMissing('pembayarans', ['sewa_id' => $other->id]);
    }

    public function test_stale_or_empty_selection_is_rejected_atomically(): void
    {
        $valid = $this->lease();
        $stale = $this->lease(['status' => 'selesai']);
        foreach ([[], [$valid->id, $stale->id], [$valid->id, 99999]] as $ids) {
            $this->postJson('/pembayarans-bulk', ['bulan' => '2026-10', 'pilih_sewa' => 1, 'sewa_ids' => $ids])
                ->assertUnprocessable();
        }
        $this->postJson('/pembayarans-bulk', ['bulan' => '2026-10', 'pilih_sewa' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('sewa_ids');
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_existing_manual_bill_on_another_day_of_month_is_preserved(): void
    {
        $lease = $this->lease();
        Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-15', 'metode' => 'transfer', 'jumlah' => 750000, 'status' => 'lunas']);
        $this->post('/pembayarans-bulk', ['bulan' => '2026-10'])->assertRedirect();
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseHas('pembayarans', ['jumlah' => 750000, 'status' => 'lunas']);
    }

    public function test_failure_on_second_invoice_rolls_back_entire_batch(): void
    {
        $this->lease();
        $this->lease();
        $calls = 0;
        Invoice::creating(function () use (&$calls): void {
            if (++$calls === 2) {
                throw new \RuntimeException('Synthetic invoice failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/pembayarans-bulk', ['bulan' => '2026-10']);
            $this->fail('Expected synthetic failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic invoice failure', $exception->getMessage());
        } finally {
            Invoice::flushEventListeners();
        }
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_paid_payment_cannot_be_changed_or_deleted(): void
    {
        $lease = $this->lease();
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 1000000, 'status' => 'lunas']);
        $this->putJson('/pembayarans/'.$payment->id, [
            'sewa_id' => $lease->id, 'periode' => '2026-10-01',
            'metode' => 'transfer', 'jumlah' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
        $this->deleteJson('/pembayarans/'.$payment->id)->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id, 'jumlah' => 1000000, 'metode' => 'cash', 'status' => 'lunas']);
        $this->assertDatabaseHas('invoices', ['jumlah_tagihan' => 1000000, 'status' => 'lunas']);
    }

    public function test_unpaid_payment_can_still_be_edited(): void
    {
        $lease = $this->lease();
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 1000000, 'status' => 'belum_lunas']);
        $this->put('/pembayarans/'.$payment->id, [
            'sewa_id' => $lease->id, 'periode' => '2026-10-01',
            'metode' => 'transfer', 'jumlah' => 750000,
        ])->assertRedirect();
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id, 'jumlah' => 750000, 'status' => 'belum_lunas']);
        $this->assertDatabaseHas('invoices', ['jumlah_tagihan' => 750000, 'status' => 'draft']);
    }

    public function test_bulk_billing_requires_create_permission(): void
    {
        $this->lease();
        $role = Role::create(['name' => 'Baca Saja', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->postJson('/pembayarans-bulk', ['bulan' => '2026-10'])->assertForbidden();
        $this->getJson('/pembayarans-bulk?bulan=2026-10')->assertForbidden();
        $this->assertDatabaseCount('pembayarans', 0);
    }
}
