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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->mock(WhatsAppService::class)->shouldIgnoreMissing();
    }

    private function payment(array $attributes = []): Pembayaran
    {
        $room = Kamar::create(['nomor' => 'LEDGER-'.Kamar::count(), 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Penghuni Ledger', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return Pembayaran::create(array_merge([
            'sewa_id' => $lease->id,
            'periode' => '2026-10-01',
            'tanggal_bayar' => '2026-10-09',
            'metode' => 'transfer',
            'jumlah' => 550000.25,
            'status' => 'belum_lunas',
        ], $attributes));
    }

    public function test_approval_creates_one_traceable_income_entry_and_retry_is_idempotent(): void
    {
        $payment = $this->payment();

        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();

        $this->assertDatabaseCount('keuangans', 1);
        $this->assertDatabaseHas('keuangans', [
            'payment_id' => $payment->id,
            'jenis' => 'pemasukan',
            'kategori' => 'Sewa Kamar',
            'jumlah' => 550000.25,
        ]);
        $entry = Keuangan::firstOrFail();
        $csv = $this->get('/keuangans/export?bulan=2026-10&jenis=pemasukan')->assertOk()->streamedContent();
        $this->assertStringContainsString('550000.25,otomatis,'.$payment->id, $csv);
        $this->assertSame('2026-10-09', $entry->tanggal->toDateString());
        $this->assertStringContainsString('Pembayaran #'.$payment->id, $entry->deskripsi);
        $this->get('/keuangans')->assertOk()->assertSee('Pembayaran #'.$payment->id);
        $this->get('/laporan-keuangan?bulan=2026-10')->assertOk()
            ->assertViewHas('totalPemasukan', fn ($total) => (float) $total === 550000.25)
            ->assertViewHas('saldo', fn ($saldo) => (float) $saldo === 550000.25);
    }

    public function test_ledger_failure_rolls_back_approval_invoice_room_and_lease(): void
    {
        $payment = $this->payment();
        $payment->sewa->update(['status' => 'menunggak']);
        DB::statement("CREATE TRIGGER fail_ledger BEFORE INSERT ON keuangans BEGIN SELECT RAISE(ABORT, 'synthetic ledger failure'); END");

        $this->postJson('/pembayarans/'.$payment->id.'/approve')->assertStatus(500);

        $this->assertSame('belum_lunas', $payment->fresh()->status);
        $this->assertSame('menunggak', $payment->sewa->fresh()->status);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'status' => 'draft']);
        $this->assertDatabaseCount('keuangans', 0);
    }

    public function test_payment_ledger_entry_cannot_be_changed_or_deleted_as_manual_finance(): void
    {
        $payment = $this->payment();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $entry = Keuangan::firstOrFail();
        $payload = ['tanggal' => '2026-10-10', 'jenis' => 'pengeluaran', 'kategori' => 'Lain-lain Pengeluaran', 'deskripsi' => 'Diubah', 'jumlah' => 1];

        $this->putJson('/keuangans/'.$entry->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('keuangan');
        $this->deleteJson('/keuangans/'.$entry->id)->assertUnprocessable()->assertJsonValidationErrors('keuangan');
        $this->get('/keuangans/'.$entry->id.'/edit')->assertRedirect('/keuangans')->assertSessionHasErrors('keuangan');
        $this->assertDatabaseHas('keuangans', ['id' => $entry->id, 'payment_id' => $payment->id, 'jenis' => 'pemasukan', 'jumlah' => 550000.25]);
    }

    public function test_database_allows_manual_entries_but_enforces_one_ledger_entry_per_payment(): void
    {
        $manual = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Manual', 'jumlah' => 230000]);
        $this->assertNull($manual->payment_id);
        $payment = $this->payment();
        $payment->update(['status' => 'lunas']);
        $first = Keuangan::create(['payment_id' => $payment->id, 'tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Sumber pertama', 'jumlah' => 550000.25]);

        $this->expectException(UniqueConstraintViolationException::class);
        Keuangan::create(['payment_id' => $payment->id, 'tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Duplikat', 'jumlah' => 550000.25]);
        $this->assertNotNull($first->id);
    }

    public function test_manual_finance_request_cannot_spoof_payment_source(): void
    {
        $payment = $this->payment();
        $this->post('/keuangans', [
            'payment_id' => $payment->id,
            'tanggal' => '2026-10-09',
            'jenis' => 'pemasukan',
            'kategori' => 'Sewa Kamar',
            'deskripsi' => 'Pencatatan manual',
            'jumlah' => 550000.25,
        ])->assertRedirect();

        $this->assertDatabaseHas('keuangans', ['deskripsi' => 'Pencatatan manual', 'payment_id' => null]);
    }

    public function test_finance_only_user_sees_source_label_without_forbidden_payment_link(): void
    {
        $payment = $this->payment();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $role = Role::create(['name' => 'Keuangan read only', 'menu_permissions' => ['keuangan.data_keuangan' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/keuangans')->assertOk()->assertSee('Sumber otomatis')->assertDontSee('/pembayarans/'.$payment->id, false);
        $this->getJson('/pembayarans/'.$payment->id)->assertForbidden();
    }

    public function test_user_without_payment_update_permission_cannot_create_ledger_by_approving(): void
    {
        $payment = $this->payment();
        $role = Role::create(['name' => 'Payment read only', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->postJson('/pembayarans/'.$payment->id.'/approve')->assertForbidden();
        $this->assertSame('belum_lunas', $payment->fresh()->status);
        $this->assertDatabaseCount('keuangans', 0);
    }

    public function test_finance_view_only_user_can_open_manual_and_automatic_details(): void
    {
        $manual = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => '<script>alert(1)</script>', 'jumlah' => 230000]);
        $payment = $this->payment();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $automatic = Keuangan::where('payment_id', $payment->id)->firstOrFail();
        $role = Role::create(['name' => 'Finance viewer', 'menu_permissions' => ['keuangan.data_keuangan' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->get('/keuangans/'.$manual->id)->assertOk()->assertSee('Internet')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('/keuangans/'.$manual->id.'/edit', false);
        $this->get('/keuangans/'.$automatic->id)->assertOk()->assertSee('Sumber otomatis')->assertDontSee('/pembayarans/'.$payment->id, false);
        $this->getJson('/keuangans/'.$manual->id.'/edit')->assertForbidden();
    }
}
