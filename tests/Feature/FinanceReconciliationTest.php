<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function payment(array $attributes = []): Pembayaran
    {
        $room = Kamar::create(['nomor' => 'FR-'.Kamar::count(), 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => '<script>alert(1)</script>', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return Pembayaran::create(array_merge(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'tanggal_bayar' => '2026-10-09', 'metode' => 'transfer', 'jumlah' => 550000.25, 'status' => 'lunas'], $attributes));
    }

    private function ledger(Pembayaran $payment, array $attributes = []): Keuangan
    {
        return Keuangan::create(array_merge(['payment_id' => $payment->id, 'tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Sintetis', 'jumlah' => 550000.25], $attributes));
    }

    public function test_read_only_report_excludes_pending_and_valid_entries_and_preserves_manual_history(): void
    {
        $this->actingAs(User::factory()->create());
        $missing = $this->payment();
        $valid = $this->payment();
        $this->ledger($valid);
        $this->payment(['status' => 'belum_lunas']);
        $wrong = $this->payment();
        $this->ledger($wrong, ['jumlah' => 550000.26]);
        $manual = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Manual', 'jumlah' => 550000.25]);
        $before = DB::table('keuangans')->orderBy('id')->get()->toJson();
        foreach (['tanpa_pemasukan', 'tidak_sesuai'] as $category) {
            $this->get('/keuangans/reconciliation?kategori='.$category)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
                ->assertViewHas('counts', ['tanpa_pemasukan' => 1, 'tidak_sesuai' => 1])
                ->assertViewHas('records', fn ($rows) => $rows->count() === 1)
                ->assertDontSee('<script>alert(1)</script>', false);
        }
        $this->assertSame($before, DB::table('keuangans')->orderBy('id')->get()->toJson());
        $this->assertNull($manual->fresh()->payment_id);
        $this->assertNull($missing->ledgerEntry);
    }

    public function test_mismatch_covers_date_kind_category_pending_and_missing_payment_date(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([['tanggal' => '2026-11-01'], ['jenis' => 'pengeluaran'], ['kategori' => 'Internet']] as $change) {
            $this->ledger($this->payment(), $change);
        }
        $this->ledger($this->payment(['status' => 'belum_lunas']));
        $this->ledger($this->payment(['tanggal_bayar' => null]));
        $this->get('/keuangans/reconciliation?kategori=tidak_sesuai')->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 5);
    }

    public function test_month_boundaries_fallback_shifted_ledger_and_invalid_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $this->payment(['tanggal_bayar' => '2026-10-01']);
        $this->payment(['tanggal_bayar' => '2026-10-31']);
        $this->payment(['tanggal_bayar' => '2026-11-01']);
        $this->payment(['tanggal_bayar' => null]);
        $this->ledger($this->payment(), ['tanggal' => '2026-11-01']);
        $this->get('/keuangans/reconciliation?bulan=2026-10')->assertOk()->assertViewHas('counts', ['tanpa_pemasukan' => 3, 'tidak_sesuai' => 1]);
        $this->get('/keuangans/reconciliation?bulan=2026-11')->assertOk()->assertViewHas('counts', ['tanpa_pemasukan' => 1, 'tidak_sesuai' => 1]);
        foreach (['bulan=2026-13', 'bulan=invalid', 'kategori=invalid'] as $query) {
            $this->getJson('/keuangans/reconciliation?'.$query)->assertUnprocessable();
        }
    }

    public function test_report_needs_both_finance_and_payment_view_permissions(): void
    {
        $this->get('/keuangans/reconciliation')->assertRedirect('/login');
        foreach ([[], ['keuangan.data_keuangan' => ['view']], ['manajemen_sewa.data_sewa' => ['view']]] as $permissions) {
            $role = Role::create(['name' => 'Role '.Role::count(), 'menu_permissions' => $permissions]);
            $this->actingAs(User::factory()->create(['role_id' => $role->id]))->getJson('/keuangans/reconciliation')->assertForbidden();
        }
        $role = Role::create(['name' => 'Reconciler', 'menu_permissions' => ['keuangan.data_keuangan' => ['view'], 'manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))->get('/keuangans/reconciliation')->assertOk();
        $this->get('/keuangans')->assertOk()->assertSee('Rekonsiliasi Pembayaran');
    }

    public function test_pagination_and_navigation_preserve_month(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 26; $i++) {
            $this->payment();
        }
        $this->get('/keuangans/reconciliation?bulan=2026-10')->assertOk()->assertViewHas('records', fn ($rows) => $rows->count() === 25 && $rows->total() === 26)->assertSee('page=2', false)->assertSee('bulan=2026-10', false);
        $this->get('/keuangans/reconciliation?bulan=2026-10&page=2')->assertOk()->assertViewHas('records', fn ($rows) => $rows->count() === 1);
    }

    public function test_report_only_offers_strict_manual_income_candidates(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();
        $exact = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Transfer exact', 'jumlah' => 550000.25]);
        foreach ([
            ['tanggal' => '2026-10-10', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Tanggal beda', 'jumlah' => 550000.25],
            ['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Nominal beda', 'jumlah' => 550000.26],
            ['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Jenis beda', 'jumlah' => 550000.25],
            ['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Lain-lain Pemasukan', 'deskripsi' => 'Kategori beda', 'jumlah' => 550000.25],
        ] as $attributes) {
            Keuangan::create($attributes);
        }
        $this->assertTrue(Keuangan::whereNull('payment_id')->whereDate('tanggal', '2026-10-09')->exists());
        $this->assertTrue(Keuangan::whereNull('payment_id')->whereDate('tanggal', '2026-10-09')->where('jumlah', '550000.25')->exists());
        $this->assertTrue(Keuangan::whereNull('payment_id')->whereDate('tanggal', '2026-10-09')->where('jumlah', '550000.25')->where('jenis', 'pemasukan')->where('kategori', 'Sewa Kamar')->exists());
        $this->assertTrue(Keuangan::whereNull('payment_id')->whereDate('tanggal', '2026-10-09')->where('jumlah', '550000.25')->where('jenis', 'pemasukan')->where('kategori', 'Sewa Kamar')->whereDoesntHave('reversalSource')->exists());

        $this->get('/keuangans/reconciliation')->assertOk()
            ->assertViewHas('candidates', fn ($rows) => $rows->flatten()->pluck('id')->all() === [$exact->id])
            ->assertSee('Transfer exact')->assertDontSee('Tanggal beda')->assertDontSee('Nominal beda');
    }

    public function test_operator_can_auditably_link_and_unlink_an_exact_manual_income(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment = $this->payment();
        $entry = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => '<script>alert(1)</script>', 'jumlah' => 550000.25]);
        $linkReason = 'Bukti transfer dan mutasi bank telah dicocokkan <script>alert(2)</script>';

        $this->postJson('/keuangans/reconciliation/'.$payment->id.'/ledger-link', [
            'keuangan_id' => $entry->id,
            'reason' => 'pendek',
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->post('/keuangans/reconciliation/'.$payment->id.'/ledger-link', [
            'keuangan_id' => $entry->id,
            'reason' => $linkReason,
        ])->assertRedirect('/keuangans/reconciliation');

        $entry->refresh();
        $this->assertSame($payment->id, $entry->payment_id);
        $this->assertSame($user->id, $entry->manually_linked_by);
        $this->assertNotNull($entry->manually_linked_at);
        $this->assertDatabaseHas('finance_payment_link_audits', ['payment_id' => $payment->id, 'ledger_entry_id' => $entry->id, 'user_id' => $user->id, 'action' => 'linked', 'reason' => $linkReason]);
        $this->get('/keuangans/'.$entry->id)->assertOk()->assertSee('Tautan rekonsiliasi manual')->assertDontSee('<script>alert(1)</script>', false);
        $csv = $this->get('/keuangans/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('rekonsiliasi manual', $csv);
        $this->assertStringContainsString(','.$payment->id, $csv);
        $this->putJson('/keuangans/'.$entry->id, ['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'ubah', 'jumlah' => 550000.25])->assertUnprocessable();

        $unlinkReason = 'Pilihan pemasukan perlu dikoreksi oleh operator';
        $this->delete('/keuangans/reconciliation/'.$payment->id.'/ledger-link', ['reason' => $unlinkReason])->assertRedirect('/keuangans/'.$entry->id);
        $entry->refresh();
        $this->assertNull($entry->payment_id);
        $this->assertNull($entry->manually_linked_by);
        $this->assertNull($entry->manually_linked_at);
        $this->assertDatabaseHas('finance_payment_link_audits', ['payment_id' => $payment->id, 'ledger_entry_id' => $entry->id, 'user_id' => $user->id, 'action' => 'unlinked', 'reason' => $unlinkReason]);
        $this->assertDatabaseCount('finance_payment_link_audits', 2);
        $this->assertDatabaseCount('keuangans', 1);
        $this->get('/keuangans/'.$entry->id)->assertOk()->assertSee($linkReason)->assertSee($unlinkReason)->assertDontSee('<script>alert(2)</script>', false);
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertSee($linkReason)->assertSee($unlinkReason)->assertDontSee('<script>alert(2)</script>', false);
    }

    public function test_manual_link_rejects_mismatch_reuse_and_automatic_income_detach(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->payment();
        $second = $this->payment();
        $wrong = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Nominal beda', 'jumlah' => 1]);
        $payload = ['keuangan_id' => $wrong->id, 'reason' => 'Pencocokan manual sintetis yang cukup panjang'];

        $this->postJson('/keuangans/reconciliation/'.$first->id.'/ledger-link', $payload)->assertUnprocessable()->assertJsonValidationErrors('keuangan_id');
        $wrong->update(['jumlah' => 550000.25]);
        $this->post('/keuangans/reconciliation/'.$first->id.'/ledger-link', $payload)->assertRedirect();
        $this->postJson('/keuangans/reconciliation/'.$second->id.'/ledger-link', $payload)->assertUnprocessable()->assertJsonValidationErrors('keuangan_id');

        $automatic = $this->ledger($second);
        $this->deleteJson('/keuangans/reconciliation/'.$second->id.'/ledger-link', ['reason' => 'Tidak boleh melepas pemasukan approval otomatis'])->assertUnprocessable()->assertJsonValidationErrors('keuangan');
        $this->assertSame($second->id, $automatic->fresh()->payment_id);
    }

    public function test_manual_link_requires_update_permissions_for_finance_and_payment(): void
    {
        $payment = $this->payment();
        $entry = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Manual', 'jumlah' => 550000.25]);
        $payload = ['keuangan_id' => $entry->id, 'reason' => 'Pencocokan manual sintetis yang cukup panjang'];
        foreach ([
            ['keuangan.data_keuangan' => ['view', 'update'], 'manajemen_sewa.data_sewa' => ['view']],
            ['keuangan.data_keuangan' => ['view'], 'manajemen_sewa.data_sewa' => ['view', 'update']],
        ] as $permissions) {
            $role = Role::create(['name' => 'Matcher '.Role::count(), 'menu_permissions' => $permissions]);
            $this->actingAs(User::factory()->create(['role_id' => $role->id]))
                ->postJson('/keuangans/reconciliation/'.$payment->id.'/ledger-link', $payload)->assertForbidden();
        }
        $this->assertNull($entry->fresh()->payment_id);
    }

    public function test_manual_link_audit_failure_rolls_back_the_association(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();
        $entry = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Manual', 'jumlah' => 550000.25]);
        DB::statement("CREATE TRIGGER fail_manual_link_audit BEFORE INSERT ON finance_payment_link_audits BEGIN SELECT RAISE(ABORT, 'synthetic audit failure'); END");

        $this->postJson('/keuangans/reconciliation/'.$payment->id.'/ledger-link', [
            'keuangan_id' => $entry->id,
            'reason' => 'Pencocokan manual sintetis yang cukup panjang',
        ])->assertStatus(500);

        $entry->refresh();
        $this->assertNull($entry->payment_id);
        $this->assertNull($entry->manually_linked_at);
        $this->assertNull($entry->manually_linked_by);
        $this->assertDatabaseCount('finance_payment_link_audits', 0);
    }
}
