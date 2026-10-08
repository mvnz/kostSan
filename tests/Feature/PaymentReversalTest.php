<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentReversalTest extends TestCase
{
    use DatabaseMigrations;

    private function approvedPayment(): Pembayaran
    {
        $room = Kamar::create(['nomor' => 'REVERSAL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Reversal sintetis', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'tanggal_bayar' => '2026-10-09', 'metode' => 'transfer', 'jumlah' => 550000.25, 'status' => 'belum_lunas']);
        $this->mock(WhatsAppService::class)->shouldIgnoreMissing();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();

        return $payment->fresh();
    }

    public function test_full_reversal_adds_immutable_offset_and_preserves_original_records(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->approvedPayment();
        $this->post('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => '2026-10-09', 'reason' => '<script>Pengembalian penuh</script>'])->assertRedirect();
        $this->assertSame('lunas', $payment->fresh()->status);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'status' => 'lunas', 'jumlah_tagihan' => 550000.25]);
        $this->assertDatabaseHas('keuangans', ['payment_id' => $payment->id, 'jenis' => 'pemasukan', 'jumlah' => 550000.25]);
        $offset = Keuangan::where('jenis', 'pengeluaran')->firstOrFail();
        $this->assertSame('550000.25', $offset->jumlah);
        $this->assertDatabaseHas('payment_reversals', ['payment_id' => $payment->id, 'reversal_entry_id' => $offset->id, 'reason' => '<script>Pengembalian penuh</script>']);
        $this->get('/laporan-keuangan?bulan=2026-10')->assertOk()->assertViewHas('saldo', fn ($saldo) => (float) $saldo === 0.0);
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertSee('Dibalik penuh')->assertDontSee('<script>Pengembalian penuh</script>', false);
        $this->get('/keuangans/'.$offset->id)->assertOk()->assertSee('Pembalikan otomatis');
        $payload = ['tanggal' => '2026-10-11', 'jenis' => 'pemasukan', 'kategori' => 'Lainnya', 'deskripsi' => 'ubah', 'jumlah' => 1];
        $this->putJson('/keuangans/'.$offset->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('keuangan');
        $this->deleteJson('/keuangans/'.$offset->id)->assertUnprocessable()->assertJsonValidationErrors('keuangan');
        $csv = $this->get('/keuangans/export?bulan=2026-10')->assertOk()->streamedContent();
        $this->assertStringContainsString('550000.25,pembalikan,'.$payment->id, $csv);
    }

    public function test_reversal_is_idempotent_and_validates_state_reason_and_source_ledger(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->approvedPayment();
        $this->postJson('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => 'invalid', 'reason' => 'x'])->assertUnprocessable()->assertJsonValidationErrors(['reversal_date', 'reason']);
        $this->postJson('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => '2026-10-08', 'reason' => 'Tanggal sebelum pembayaran'])->assertUnprocessable()->assertJsonValidationErrors('reversal_date');
        $this->postJson('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => now()->addDay()->toDateString(), 'reason' => 'Tanggal masa depan'])->assertUnprocessable()->assertJsonValidationErrors('reversal_date');
        $payload = ['reversal_date' => '2026-10-09', 'reason' => 'Pengembalian penuh'];
        $this->post('/pembayarans/'.$payment->id.'/reverse', $payload)->assertRedirect();
        $this->post('/pembayarans/'.$payment->id.'/reverse', $payload)->assertRedirect();
        $this->assertDatabaseCount('payment_reversals', 1);
        $this->assertDatabaseCount('keuangans', 2);
        $other = Pembayaran::create(['sewa_id' => $payment->sewa_id, 'periode' => '2026-11-01', 'metode' => 'cash', 'jumlah' => 550000, 'status' => 'belum_lunas']);
        $this->postJson('/pembayarans/'.$other->id.'/reverse', $payload)->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
        $other->update(['status' => 'lunas']);
        $this->postJson('/pembayarans/'.$other->id.'/reverse', $payload)->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
    }

    public function test_mismatched_source_must_be_reconciled_before_reversal(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->approvedPayment();
        $payment->ledgerEntry()->update(['jumlah' => 1]);
        $this->postJson('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => '2026-10-09', 'reason' => 'Pengembalian penuh'])->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
        $this->assertDatabaseCount('payment_reversals', 0);
        $this->assertDatabaseCount('keuangans', 1);
    }

    public function test_reversal_failure_rolls_back_offset_and_can_retry(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->approvedPayment();
        DB::statement("CREATE TRIGGER fail_reversal BEFORE INSERT ON payment_reversals BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $payload = ['reversal_date' => '2026-10-09', 'reason' => 'Pengembalian penuh'];
        $this->postJson('/pembayarans/'.$payment->id.'/reverse', $payload)->assertStatus(500);
        $this->assertDatabaseCount('payment_reversals', 0);
        $this->assertDatabaseCount('keuangans', 1);
        DB::statement('DROP TRIGGER fail_reversal');
        $this->post('/pembayarans/'.$payment->id.'/reverse', $payload)->assertRedirect();
        $this->assertDatabaseCount('payment_reversals', 1);
        $this->assertDatabaseCount('keuangans', 2);
    }

    public function test_reversal_requires_payment_update_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->approvedPayment();
        $role = Role::create(['name' => 'Read only reversal', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))->postJson('/pembayarans/'.$payment->id.'/reverse', ['reversal_date' => '2026-10-09', 'reason' => 'Pengembalian penuh'])->assertForbidden();
        $this->assertDatabaseCount('payment_reversals', 0);
        $this->assertDatabaseCount('keuangans', 1);
    }
}
