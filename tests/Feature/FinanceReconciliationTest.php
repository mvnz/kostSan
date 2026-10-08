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
}
