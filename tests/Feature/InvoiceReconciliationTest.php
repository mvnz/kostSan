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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Pembayaran
    {
        $room = Kamar::create(['nomor' => 'REC-'.Kamar::count(), 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => '<script>alert(1)</script>', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'jumlah' => 550000.25, 'metode' => 'transfer', 'status' => 'belum_lunas']);
    }

    public function test_report_classifies_legacy_missing_and_mismatched_without_mutating_data(): void
    {
        $this->actingAs(User::factory()->create());
        $normal = $this->payment();
        $missing = $this->payment();
        Invoice::where('payment_id', $missing->id)->delete();
        $mismatch = $this->payment();
        DB::table('invoices')->where('payment_id', $mismatch->id)->update(['jumlah_tagihan' => 550000.26]);
        $legacy = Invoice::create(['penghuni_id' => $normal->sewa->penghuni_id, 'nomor_invoice' => 'LEGACY-AUTO', 'periode' => '2026-10-01', 'jatuh_tempo' => '2026-10-10', 'jumlah_tagihan' => 100, 'status' => 'draft', 'keterangan' => 'AUTO: lama']);
        Invoice::create(['penghuni_id' => $normal->sewa->penghuni_id, 'nomor_invoice' => 'MANUAL-VALID', 'periode' => '2026-10-01', 'jatuh_tempo' => '2026-10-10', 'jumlah_tagihan' => 100, 'status' => 'draft']);
        $before = DB::table('invoices')->orderBy('id')->get()->toJson();
        foreach (['legacy', 'tanpa_invoice', 'tidak_sesuai'] as $category) {
            $response = $this->get('/invoices/reconciliation?kategori='.$category)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
            $response->assertViewHas('counts', ['legacy' => 1, 'tanpa_invoice' => 1, 'tidak_sesuai' => 1]);
            $response->assertViewHas('records', fn ($records) => $records->count() === 1);
            $response->assertDontSee('<script>alert(1)</script>', false);
        }
        $this->assertSame($before, DB::table('invoices')->orderBy('id')->get()->toJson());
        $this->assertNull($legacy->fresh()->payment_id);
    }

    public function test_report_requires_invoice_view_permission_and_login(): void
    {
        $this->get('/invoices/reconciliation')->assertRedirect('/login');
        $role = Role::create(['name' => 'No invoice', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))->getJson('/invoices/reconciliation')->assertForbidden();
        $role->update(['menu_permissions' => ['keuangan.invoice' => ['view']]]);
        $this->actingAs(User::find(auth()->id()))->get('/invoices/reconciliation')->assertOk();
    }

    public function test_invalid_category_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/invoices/reconciliation?kategori=anything')->assertUnprocessable()->assertJsonValidationErrors('kategori');
    }

    public function test_report_paginates_and_preserves_category(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();
        for ($i = 0; $i < 26; $i++) {
            Invoice::create(['penghuni_id' => $payment->sewa->penghuni_id, 'nomor_invoice' => 'LEGACY-'.$i, 'periode' => '2026-10-01', 'jatuh_tempo' => '2026-10-10', 'jumlah_tagihan' => 100, 'status' => 'draft', 'keterangan' => 'AUTO: lama']);
        }
        $this->get('/invoices/reconciliation?kategori=legacy')->assertOk()->assertViewHas('records', fn ($records) => $records->count() === 25 && $records->total() === 26)->assertSee('kategori=legacy', false);
        $this->get('/invoices/reconciliation?kategori=legacy&page=2')->assertOk()->assertViewHas('records', fn ($records) => $records->count() === 1);
    }

    public function test_report_detects_each_identity_and_status_mismatch_but_accepts_sent_pending_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();
        $invoice = Invoice::where('payment_id', $payment->id)->firstOrFail();
        $baseline = $invoice->getAttributes();
        $other = Penghuni::create(['nama' => 'Penghuni lain', 'telepon' => '']);
        foreach ([['periode' => '2026-11-01'], ['penghuni_id' => $other->id], ['status' => 'lunas']] as $change) {
            DB::table('invoices')->where('id', $invoice->id)->update($change);
            $this->get('/invoices/reconciliation?kategori=tidak_sesuai')->assertOk()->assertViewHas('counts', fn ($counts) => $counts['tidak_sesuai'] === 1);
            DB::table('invoices')->where('id', $invoice->id)->update($baseline);
        }
        DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'terkirim']);
        $this->get('/invoices/reconciliation?kategori=tidak_sesuai')->assertViewHas('counts', fn ($counts) => $counts['tidak_sesuai'] === 0);
        DB::table('pembayarans')->where('id', $payment->id)->update(['status' => 'lunas']);
        $this->get('/invoices/reconciliation?kategori=tidak_sesuai')->assertViewHas('counts', fn ($counts) => $counts['tidak_sesuai'] === 1);
    }

    public function test_month_filter_includes_both_sides_of_shifted_period_and_counts_only_matching_records(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();
        DB::table('invoices')->where('payment_id', $payment->id)->update(['periode' => '2026-11-01']);
        $missing = $this->payment();
        Invoice::where('payment_id', $missing->id)->delete();
        DB::table('pembayarans')->where('id', $missing->id)->update(['periode' => '2026-10-31']);
        foreach (['2026-09-30', '2026-10-01', '2026-10-31', '2026-11-01'] as $i => $date) {
            Invoice::create(['penghuni_id' => $payment->sewa->penghuni_id, 'nomor_invoice' => 'MONTH-'.$i, 'periode' => $date, 'jatuh_tempo' => '2026-12-01', 'jumlah_tagihan' => 100, 'status' => 'draft', 'keterangan' => 'AUTO: lama']);
        }
        $this->get('/invoices/reconciliation?bulan=2026-10')
            ->assertOk()->assertViewHas('counts', ['legacy' => 2, 'tanpa_invoice' => 1, 'tidak_sesuai' => 1])
            ->assertSee('MONTH-1')->assertSee('MONTH-2')->assertDontSee('MONTH-0')->assertDontSee('MONTH-3');
        $this->get('/invoices/reconciliation?kategori=tidak_sesuai&bulan=2026-11')
            ->assertOk()->assertViewHas('counts', ['legacy' => 1, 'tanpa_invoice' => 0, 'tidak_sesuai' => 1])
            ->assertViewHas('records', fn ($records) => $records->count() === 1);
        $this->get('/invoices/reconciliation?bulan=2026-12')->assertViewHas('counts', ['legacy' => 0, 'tanpa_invoice' => 0, 'tidak_sesuai' => 0]);
        $this->get('/invoices/reconciliation')->assertViewHas('counts', ['legacy' => 4, 'tanpa_invoice' => 1, 'tidak_sesuai' => 1]);
    }

    public function test_month_filter_rejects_invalid_input_and_survives_category_and_pagination_navigation(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['2026-13', '2026-2', '2026-02-01', '<script>', '2026-00'] as $month) {
            $this->getJson('/invoices/reconciliation?'.http_build_query(['bulan' => $month]))
                ->assertUnprocessable()->assertJsonValidationErrors('bulan');
        }
        $payment = $this->payment();
        for ($i = 0; $i < 26; $i++) {
            Invoice::create(['penghuni_id' => $payment->sewa->penghuni_id, 'nomor_invoice' => 'FILTER-'.$i, 'periode' => '2026-10-01', 'jatuh_tempo' => '2026-10-10', 'jumlah_tagihan' => 100, 'status' => 'draft', 'keterangan' => 'AUTO: lama']);
        }
        $this->get('/invoices/reconciliation?kategori=legacy&bulan=2026-10')
            ->assertOk()->assertSee('bulan=2026-10', false)
            ->assertViewHas('records', fn ($records) => $records->total() === 26 && str_contains($records->nextPageUrl(), 'bulan=2026-10'));
        $this->get('/invoices/reconciliation?kategori=legacy&bulan=2026-10&page=2')
            ->assertOk()->assertSee('FILTER-25')->assertViewHas('records', fn ($records) => $records->count() === 1);
        $this->get('/invoices/reconciliation?bulan=2026-02')->assertOk()->assertSee('Tidak ada data');
    }
}
